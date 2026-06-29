<?php

namespace StudyRoomTechLab\IspSms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Isp;
use StudyRoomTechLab\IspSms\Models\IspSmsMessage;
use StudyRoomTechLab\IspSms\Models\IspSmsSetting;
use StudyRoomTechLab\IspSms\Models\IspSmsTemplate;
use StudyRoomTechLab\IspSms\Services\SmsManager;
use App\Models\User;
use App\Services\IspTenantResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class IspSmsController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeView($request);

        $messages = collect();
        $stats = [
            'total' => 0,
            'queued' => 0,
            'sent' => 0,
            'delivered' => 0,
            'failed' => 0,
        ];

        if (Schema::hasTable('isp_sms_messages')) {
            $isp = $this->isPlatform($request) ? null : $this->resolveIsp($request);

            $baseQuery = IspSmsMessage::query()
                ->when($isp, fn ($query) => $query->where('isp_id', $isp->id));

            $stats = [
                'total' => (clone $baseQuery)->count(),
                'queued' => (clone $baseQuery)->where('status', 'queued')->count(),
                'sent' => (clone $baseQuery)->where('status', 'sent')->count(),
                'delivered' => (clone $baseQuery)->where('status', 'delivered')->count(),
                'failed' => (clone $baseQuery)->where('status', 'failed')->count(),
            ];

            $query = IspSmsMessage::query()
                ->with(['isp', 'customer', 'sender', 'recipientUser'])
                ->when($isp, fn ($query) => $query->where('isp_id', $isp->id));

            if ($search = trim((string) $request->query('q'))) {
                $query->where(function ($query) use ($search) {
                    $query->where('phone', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%")
                        ->orWhere('provider_message_id', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%");
                        })
                        ->orWhereHas('recipientUser', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('mobile_no', 'like', "%{$search}%");
                        });
                });
            }

            if ($status = $request->query('status')) {
                $query->where('status', $status);
            }

            if ($mode = $request->query('sending_mode')) {
                $query->where('sending_mode', $mode);
            }

            if ($direction = $request->query('direction')) {
                $query->where('direction', $direction);
            }

            $messages = $query
                ->latest()
                ->paginate(15)
                ->withQueryString();
        }

        return $this->viewOrPlaceholder('messages.index', [
            'messages' => $messages,
            'stats' => $stats,
            'hasSmsTables' => Schema::hasTable('isp_sms_messages'),
            'filters' => $request->only(['q', 'status', 'sending_mode', 'direction']),
            'isPlatform' => $this->isPlatform($request),
        ], [
            'title' => 'SMS Messages',
            'subtitle' => 'View and send SMS messages to WiFi customers.',
            'status' => 'Ready for setup',
            'columns' => ['Recipient', 'Phone', 'Mode', 'Message', 'Status', 'Sent At', 'Action'],
            'note' => 'SMS logs are stored locally per ISP. Real provider sending will be connected in the next step.',
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizeManage($request);

        $isPlatform = $this->isPlatform($request);
        $isp = $isPlatform ? null : $this->resolveIsp($request);

        return $this->viewOrPlaceholder('messages.create', [
            'customers' => Customer::query()
                ->with(['isp', 'internetPackage'])
                ->when($isp, fn ($query) => $query->where('isp_id', $isp->id))
                ->orderBy('name')
                ->get(),
            'users' => $isPlatform
                ? User::query()
                    ->whereIn('type', ['company', 'isp_admin', 'admin'])
                    ->orderBy('name')
                    ->get()
                : collect(),
            'templates' => Schema::hasTable('isp_sms_templates')
                ? IspSmsTemplate::query()
                    ->when($isp, fn ($query) => $query->where(function ($subQuery) use ($isp) {
                        $subQuery->whereNull('isp_id')->orWhere('isp_id', $isp->id);
                    }))
                    ->where('enabled', true)
                    ->orderBy('name')
                    ->get()
                : collect(),
            'isPlatform' => $isPlatform,
        ], [
            'title' => 'Send SMS',
            'subtitle' => 'Compose an SMS message for a WiFi customer.',
            'status' => 'Ready for setup',
            'columns' => ['Recipient', 'Message', 'Mode', 'Status'],
            'note' => 'Create the SMS views in Step 3 to use this form.',
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeManage($request);

        abort_unless(Schema::hasTable('isp_sms_messages'), 500, 'SMS tables are not migrated yet.');

        $data = $request->validate([
            'recipient_type' => ['nullable', Rule::in(['customer', 'user', 'phone'])],
            'customer_id' => ['nullable', 'exists:isp_customers,id'],
            'recipient_user_id' => ['nullable', 'exists:users,id'],
            'isp_id' => ['nullable', 'exists:isps,id'],
            'phone' => ['nullable', 'string', 'max:40'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $isPlatform = $this->isPlatform($request);
        $recipientType = $data['recipient_type'] ?? $this->guessRecipientType($data);

        if (! $isPlatform && $recipientType !== 'customer') {
            throw ValidationException::withMessages([
                'customer_id' => 'ISP admins can send SMS only to their own WiFi customers.',
            ]);
        }

        [$isp, $customer, $recipientUser, $phone] = $this->resolveRecipient($request, $recipientType, $data);

        if (! $phone) {
            throw ValidationException::withMessages([
                'phone' => 'The selected recipient does not have a phone number.',
            ]);
        }

        $setting = $this->activeSetting((int) $isp->id);

        $message = IspSmsMessage::create([
            'isp_id' => $isp->id,
            'customer_id' => $customer?->id,
            'recipient_user_id' => $recipientUser?->id,
            'phone' => $phone,
            'message' => $data['message'],
            'channel' => 'sms',
            'direction' => 'outbound',
            'sending_mode' => $setting?->mode ?: 'platform',
            'provider' => $setting?->provider ?: 'platform',
            'status' => 'queued',
            'sent_by' => $request->user()->id,
        ]);

        $message = app(SmsManager::class)->send($message);

        return redirect()
            ->route('isp.sms.show', $message)
            ->with('success', $message->status === 'sent'
                ? 'SMS sent successfully.'
                : 'SMS saved but not sent: ' . ($message->result_message ?: 'Check SMS settings.'));
    }

    public function show(Request $request, IspSmsMessage $message)
    {
        $this->authorizeView($request);
        $this->authorizeIspRecord($request, (int) $message->isp_id);

        return $this->viewOrPlaceholder('messages.show', [
            'message' => $message->load(['isp', 'customer', 'sender', 'recipientUser']),
        ], [
            'title' => 'SMS Message',
            'subtitle' => 'View SMS delivery details.',
            'status' => ucfirst($message->status),
            'columns' => ['Phone', 'Message', 'Status', 'Provider', 'Sent At'],
            'note' => 'Create the SMS detail view in Step 3.',
        ]);
    }

    private function guessRecipientType(array $data): string
    {
        if (! empty($data['customer_id'])) {
            return 'customer';
        }

        if (! empty($data['recipient_user_id'])) {
            return 'user';
        }

        return 'phone';
    }

    private function resolveRecipient(Request $request, string $recipientType, array $data): array
    {
        $customer = null;
        $recipientUser = null;
        $phone = null;
        $isp = null;

        if ($recipientType === 'customer') {
            if (empty($data['customer_id'])) {
                throw ValidationException::withMessages([
                    'customer_id' => 'Select a WiFi customer.',
                ]);
            }

            $customer = Customer::with('isp')->findOrFail($data['customer_id']);
            $this->authorizeIspRecord($request, (int) $customer->isp_id);

            $isp = $customer->isp;
            $phone = $customer->phone;
        }

        if ($recipientType === 'user') {
            abort_unless($this->isPlatform($request), 403, 'Only platform users can send SMS to admin users.');

            if (empty($data['recipient_user_id'])) {
                throw ValidationException::withMessages([
                    'recipient_user_id' => 'Select an admin/company user.',
                ]);
            }

            $recipientUser = User::findOrFail($data['recipient_user_id']);
            $isp = $recipientUser->isp ?: $this->resolveIsp($request, $recipientUser->isp_id);
            $phone = $recipientUser->mobile_no;
        }

        if ($recipientType === 'phone') {
            abort_unless($this->isPlatform($request), 403, 'Only platform users can send SMS to a custom phone number.');

            $phone = $data['phone'] ?? null;

            if (! $phone) {
                throw ValidationException::withMessages([
                    'phone' => 'Enter a phone number.',
                ]);
            }

            $isp = $this->resolveIsp($request, $data['isp_id'] ?? null);
        }

        abort_unless($isp, 403, 'No ISP context was found for this SMS.');

        return [$isp, $customer, $recipientUser, $phone];
    }

    private function activeSetting(int $ispId): ?IspSmsSetting
    {
        if (! Schema::hasTable('isp_sms_settings')) {
            return null;
        }

        $ispSetting = IspSmsSetting::where('scope', 'isp')
            ->where('isp_id', $ispId)
            ->where('is_active', true)
            ->first();

        if ($ispSetting && $ispSetting->mode === 'own') {
            return $ispSetting;
        }

        $platformSetting = IspSmsSetting::where('scope', 'platform')
            ->whereNull('isp_id')
            ->where('is_active', true)
            ->first();

        return $platformSetting ?: $ispSetting;
    }

    private function resolveIsp(Request $request, $ispId = null): Isp
    {
        return app(IspTenantResolver::class)->resolve($request, $ispId);
    }

    private function isPlatform(Request $request): bool
    {
        return app(IspTenantResolver::class)->isPlatform($request);
    }

    private function authorizeIspRecord(Request $request, int $ispId): void
    {
        app(IspTenantResolver::class)->authorize($request, $ispId);
    }

    private function authorizeView(Request $request): void
    {
        abort_unless(
            $this->isPlatform($request)
            || $request->user()->can('view-wifi-dashboard')
            || $request->user()->can('manage-wifi-dashboard')
            || $request->user()->can('view-isp-customers')
            || $request->user()->can('manage-isp-customers'),
            403
        );
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless(
            $this->isPlatform($request)
            || $request->user()->can('manage-wifi-dashboard')
            || $request->user()->can('manage-isp-customers'),
            403
        );
    }

    private function viewOrPlaceholder(string $view, array $data, array $page)
    {
        $packageView = 'isp-sms::' . $view;

        if (view()->exists($packageView)) {
            return view($packageView, $data);
        }

        if (view()->exists($view)) {
            return view($view, $data);
        }

        return view('isp-modules.placeholder', [
            'page' => $page,
        ]);
    }
}