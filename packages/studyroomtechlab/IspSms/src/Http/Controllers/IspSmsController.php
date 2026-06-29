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
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class IspSmsController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeView($request);

        $messages = new LengthAwarePaginator([], 0, 15, 1, [
            'path' => $request->url(),
        ]);
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
                ->through(fn (IspSmsMessage $message) => $this->messagePayload($message))
                ->withQueryString();
        }

        return Inertia::render('sms/index', [
            'pageTitle' => 'SMS Messages',
            'subtitle' => 'Send and monitor ISP customer SMS logs from the main app workspace.',
            'messages' => $messages,
            'stats' => $stats,
            'hasSmsTables' => Schema::hasTable('isp_sms_messages'),
            'filters' => $request->only(['q', 'status', 'sending_mode', 'direction']),
            'isPlatform' => $this->isPlatform($request),
            'routes' => [
                'index' => route('isp.sms.index'),
                'newMessage' => route('isp.sms.new-message'),
                'settings' => route('isp.sms.settings'),
                'templates' => route('isp.sms.templates.index'),
            ],
        ]);
    }

    public function create(Request $request)
    {
        return $this->newMessage($request);
    }

    public function newMessage(Request $request)
    {
        $this->authorizeManage($request);

        abort_unless(Schema::hasTable('isp_sms_messages'), 500, 'SMS tables are not migrated yet.');

        $isPlatform = $this->isPlatform($request);
        $isp = $isPlatform ? null : $this->resolveIsp($request);
        $ispId = $isp?->id ? (int) $isp->id : null;

        return Inertia::render('sms/new-message', [
            'pageTitle' => 'New SMS Message',
            'subtitle' => 'Send customer SMS from the existing ISP SMS outbox and gateway pipeline.',
            'customers' => $this->composerCustomers($ispId),
            'segments' => $this->composerSegments($ispId),
            'routers' => $this->composerRouters($ispId),
            'templates' => $this->composerTemplates($ispId),
            'routes' => [
                'index' => route('isp.sms.index'),
                'send' => route('isp.sms.new-message.send'),
                'settings' => route('isp.sms.settings'),
                'templates' => route('isp.sms.templates.index'),
            ],
            'isPlatform' => $isPlatform,
        ]);
    }

    public function sendNewMessage(Request $request)
    {
        $this->authorizeManage($request);

        abort_unless(Schema::hasTable('isp_sms_messages'), 500, 'SMS tables are not migrated yet.');

        $data = $request->validate([
            'audience' => ['required', Rule::in(['specific', 'segment', 'mikrotik', 'everyone'])],
            'customer_ids' => ['array'],
            'customer_ids.*' => ['integer', 'exists:isp_customers,id'],
            'segment' => ['nullable', Rule::in(array_keys($this->segmentDefinitions()))],
            'router_id' => ['nullable', 'integer'],
            'message' => ['required', 'string', 'max:480'],
            'confirm_everyone' => ['nullable', 'boolean'],
        ]);

        if (($data['audience'] ?? '') === 'everyone' && ! $request->boolean('confirm_everyone')) {
            throw ValidationException::withMessages([
                'confirm_everyone' => 'Confirm that you want to send this SMS to every customer with a valid phone number.',
            ]);
        }

        $isPlatform = $this->isPlatform($request);
        $isp = $isPlatform ? null : $this->resolveIsp($request);
        $recipients = $this->resolveComposerRecipients($request, $data, $isp?->id ? (int) $isp->id : null);

        if (empty($recipients)) {
            throw ValidationException::withMessages([
                'audience' => 'No customers with valid phone numbers were found for this audience.',
            ]);
        }

        $manager = app(SmsManager::class);
        $messages = collect();

        foreach ($recipients as $recipient) {
            $recipientIspId = (int) ($recipient['isp_id'] ?? 0);

            if ($recipientIspId <= 0) {
                continue;
            }

            $setting = $this->activeSetting($recipientIspId);
            $customer = $recipient['customer'];

            $message = IspSmsMessage::create([
                'isp_id' => $recipientIspId,
                'customer_id' => $customer->id,
                'recipient_user_id' => null,
                'phone' => $recipient['phone'],
                'message' => $this->personalizeMessage($data['message'], $customer),
                'channel' => 'sms',
                'direction' => 'outbound',
                'sending_mode' => $setting?->mode ?: 'platform',
                'provider' => $setting?->provider ?: 'platform',
                'status' => 'queued',
                'sent_by' => $request->user()->id,
            ]);

            $messages->push($manager->send($message));
        }

        if ($messages->isEmpty()) {
            throw ValidationException::withMessages([
                'audience' => 'No customers with a valid ISP context were found for this audience.',
            ]);
        }

        $sent = $messages->where('status', 'sent')->count();
        $failed = $messages->where('status', 'failed')->count();
        $queued = $messages->count() - $sent - $failed;

        $summary = sprintf(
            'SMS saved for %d recipient(s): %d sent, %d queued, %d failed.',
            $messages->count(),
            $sent,
            $queued,
            $failed
        );

        return redirect()
            ->route('isp.sms.index')
            ->with($failed > 0 ? 'error' : 'success', $summary);
    }

    public function settings(Request $request)
    {
        $this->authorizeManage($request);

        $isPlatform = $this->isPlatform($request);
        $isp = $isPlatform ? null : $this->resolveIsp($request);
        $setting = null;
        $platformSetting = null;

        if (Schema::hasTable('isp_sms_settings')) {
            if ($isp) {
                $setting = IspSmsSetting::where('scope', 'isp')
                    ->where('isp_id', $isp->id)
                    ->first();
            }

            $platformSetting = IspSmsSetting::where('scope', 'platform')
                ->whereNull('isp_id')
                ->first();
        }

        return Inertia::render('sms/settings', [
            'pageTitle' => 'SMS Settings',
            'subtitle' => 'Choose platform SMS or an ISP-owned SMS gateway.',
            'setting' => $this->settingPayload($setting),
            'platformSetting' => $this->settingPayload($platformSetting),
            'isPlatform' => $isPlatform,
            'hasSmsTables' => Schema::hasTable('isp_sms_settings'),
            'dryRun' => (bool) config('isp-sms.dry_run', true),
            'routes' => [
                'messages' => route('isp.sms.index'),
                'newMessage' => route('isp.sms.new-message'),
                'save' => route('isp.sms.settings.save'),
                'templates' => route('isp.sms.templates.index'),
            ],
        ]);
    }

    public function templates(Request $request)
    {
        $this->authorizeView($request);

        $templates = new LengthAwarePaginator([], 0, 15, 1, [
            'path' => $request->url(),
        ]);
        $isPlatform = $this->isPlatform($request);
        $isp = $isPlatform ? null : $this->resolveIsp($request);

        if (Schema::hasTable('isp_sms_templates')) {
            $templates = IspSmsTemplate::query()
                ->when($isp, fn ($query) => $query->where(function ($subQuery) use ($isp) {
                    $subQuery->whereNull('isp_id')->orWhere('isp_id', $isp->id);
                }))
                ->when($isPlatform && $request->query('scope') === 'platform', fn ($query) => $query->whereNull('isp_id'))
                ->orderBy('name')
                ->paginate(15)
                ->through(fn (IspSmsTemplate $template) => $this->templatePayload($template))
                ->withQueryString();
        }

        return Inertia::render('sms/templates', [
            'pageTitle' => 'SMS Templates',
            'subtitle' => 'Reusable SMS copy for customer communication.',
            'templates' => $templates,
            'hasSmsTables' => Schema::hasTable('isp_sms_templates'),
            'isPlatform' => $isPlatform,
            'filters' => $request->only(['scope']),
            'routes' => [
                'messages' => route('isp.sms.index'),
                'newMessage' => route('isp.sms.new-message'),
                'settings' => route('isp.sms.settings'),
                'store' => route('isp.sms.templates.store'),
            ],
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

        return Inertia::render('sms/show', [
            'pageTitle' => 'SMS Message',
            'message' => $this->messagePayload($message->load(['isp', 'customer', 'sender', 'recipientUser'])),
            'routes' => [
                'messages' => route('isp.sms.index'),
                'newMessage' => route('isp.sms.new-message'),
                'settings' => route('isp.sms.settings'),
                'templates' => route('isp.sms.templates.index'),
            ],
        ]);
    }

    private function messagePayload(IspSmsMessage $message): array
    {
        return [
            'id' => $message->id,
            'recipient' => $message->customer?->name
                ?: $message->recipientUser?->name
                ?: 'Custom Phone',
            'customer' => $message->customer ? [
                'id' => $message->customer->id,
                'name' => $message->customer->name,
                'username' => $message->customer->username,
            ] : null,
            'recipient_user' => $message->recipientUser ? [
                'id' => $message->recipientUser->id,
                'name' => $message->recipientUser->name,
                'email' => $message->recipientUser->email,
            ] : null,
            'isp' => $message->isp ? [
                'id' => $message->isp->id,
                'name' => $message->isp->name,
            ] : null,
            'phone' => $message->phone,
            'message' => $message->message,
            'channel' => $message->channel,
            'direction' => $message->direction,
            'sending_mode' => $message->sending_mode,
            'provider' => $message->provider,
            'status' => $message->status,
            'provider_message_id' => $message->provider_message_id,
            'provider_response' => $message->provider_response,
            'result_message' => $message->result_message,
            'sender' => $message->sender ? [
                'id' => $message->sender->id,
                'name' => $message->sender->name,
            ] : null,
            'sent_at' => $this->dateTimeValue($message->sent_at),
            'delivered_at' => $this->dateTimeValue($message->delivered_at),
            'failed_at' => $this->dateTimeValue($message->failed_at),
            'created_at' => $this->dateTimeValue($message->created_at),
            'show_url' => route('isp.sms.show', $message),
        ];
    }

    private function settingPayload(?IspSmsSetting $setting): ?array
    {
        if (! $setting) {
            return null;
        }

        return [
            'id' => $setting->id,
            'scope' => $setting->scope,
            'mode' => $setting->mode,
            'provider' => $setting->provider,
            'sender_id' => $setting->sender_id,
            'username' => $setting->username,
            'callback_url' => $setting->callback_url,
            'is_active' => (bool) $setting->is_active,
            'updated_at' => $this->dateTimeValue($setting->updated_at),
        ];
    }

    private function templatePayload(IspSmsTemplate $template): array
    {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'key' => $template->key,
            'body' => $template->body,
            'enabled' => (bool) $template->enabled,
            'scope' => $template->isp_id ? 'isp' : 'platform',
            'updated_at' => $this->dateTimeValue($template->updated_at),
        ];
    }

    private function composerCustomers(?int $ispId): array
    {
        return $this->customerAudienceQuery($ispId)
            ->orderBy('name')
            ->limit(500)
            ->get()
            ->map(function (Customer $customer) {
                $normalizedPhone = $this->normalizePhone($customer->phone);

                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'normalized_phone' => $normalizedPhone,
                    'username' => $customer->username,
                    'email' => $customer->email,
                    'connection_status' => $customer->connection_status,
                    'billing_status' => $customer->billing_status,
                    'provisioning_status' => $customer->provisioning_status,
                    'next_due_date' => $this->dateValue($customer->next_due_date),
                    'has_valid_phone' => $normalizedPhone !== null,
                    'package' => $customer->internetPackage ? [
                        'id' => $customer->internetPackage->id,
                        'name' => $customer->internetPackage->name,
                    ] : null,
                    'router' => $customer->mikrotikRouter ? [
                        'id' => $customer->mikrotikRouter->id,
                        'name' => $customer->mikrotikRouter->name,
                    ] : null,
                ];
            })
            ->values()
            ->all();
    }

    private function composerSegments(?int $ispId): array
    {
        return collect($this->segmentDefinitions())
            ->map(function (array $definition, string $key) use ($ispId) {
                $query = $this->customerAudienceQuery($ispId);
                $this->applySegment($query, $key);

                return [
                    'key' => $key,
                    'label' => $definition['label'],
                    'description' => $definition['description'],
                    'count' => (clone $query)
                        ->whereNotNull('phone')
                        ->where('phone', '<>', '')
                        ->count(),
                ];
            })
            ->values()
            ->all();
    }

    private function composerRouters(?int $ispId): array
    {
        if (! Schema::hasTable('mikrotik_routers')) {
            return [];
        }

        $customerCounts = Customer::query()
            ->when($ispId, fn ($query) => $query->where('isp_id', $ispId))
            ->whereNotNull('mikrotik_router_id')
            ->whereNotNull('phone')
            ->where('phone', '<>', '')
            ->select('mikrotik_router_id', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('mikrotik_router_id')
            ->pluck('aggregate', 'mikrotik_router_id');

        return DB::table('mikrotik_routers')
            ->select(['id', 'name', 'host', 'status'])
            ->when($ispId, fn ($query) => $query->where('isp_id', $ispId))
            ->orderBy('name')
            ->get()
            ->map(fn ($router) => [
                'id' => $router->id,
                'name' => $router->name,
                'host' => $router->host,
                'status' => $router->status,
                'customer_count' => (int) ($customerCounts[$router->id] ?? 0),
            ])
            ->values()
            ->all();
    }

    private function composerTemplates(?int $ispId): array
    {
        if (! Schema::hasTable('isp_sms_templates')) {
            return [];
        }

        return IspSmsTemplate::query()
            ->when($ispId, fn ($query) => $query->where(function ($subQuery) use ($ispId) {
                $subQuery->whereNull('isp_id')->orWhere('isp_id', $ispId);
            }))
            ->where('enabled', true)
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name', 'body'])
            ->map(fn (IspSmsTemplate $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'body' => $template->body,
            ])
            ->values()
            ->all();
    }

    private function resolveComposerRecipients(Request $request, array $data, ?int $ispId): array
    {
        $query = $this->customerAudienceQuery($ispId);
        $audience = $data['audience'] ?? 'specific';

        if ($audience === 'specific') {
            $customerIds = collect($data['customer_ids'] ?? [])
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($customerIds->isEmpty()) {
                throw ValidationException::withMessages([
                    'customer_ids' => 'Select at least one customer.',
                ]);
            }

            $query->whereIn('id', $customerIds->all());
        }

        if ($audience === 'segment') {
            $segment = $data['segment'] ?? '';

            if (! array_key_exists($segment, $this->segmentDefinitions())) {
                throw ValidationException::withMessages([
                    'segment' => 'Select a valid customer segment.',
                ]);
            }

            $this->applySegment($query, $segment);
        }

        if ($audience === 'mikrotik') {
            $routerId = (int) ($data['router_id'] ?? 0);

            if ($routerId <= 0) {
                throw ValidationException::withMessages([
                    'router_id' => 'Select a MikroTik router.',
                ]);
            }

            $query->where('mikrotik_router_id', $routerId);
        }

        $customers = $query
            ->whereNotNull('phone')
            ->where('phone', '<>', '')
            ->orderBy('name')
            ->get();

        $recipients = [];
        $seenPhones = [];

        foreach ($customers as $customer) {
            $phone = $this->normalizePhone($customer->phone);

            if (! $phone || isset($seenPhones[$phone])) {
                continue;
            }

            $seenPhones[$phone] = true;
            $recipients[] = [
                'isp_id' => $customer->isp_id,
                'customer' => $customer,
                'phone' => $phone,
            ];
        }

        return $recipients;
    }

    private function customerAudienceQuery(?int $ispId)
    {
        return Customer::query()
            ->with(['isp', 'internetPackage', 'mikrotikRouter'])
            ->when($ispId, fn ($query) => $query->where('isp_id', $ispId));
    }

    private function segmentDefinitions(): array
    {
        return [
            'active_subscribers' => [
                'label' => 'Active subscribers',
                'description' => 'Customers with an active connection status.',
            ],
            'expired_package' => [
                'label' => 'Expired package',
                'description' => 'Customers whose next due date has passed.',
            ],
            'pending_payment' => [
                'label' => 'Pending payment',
                'description' => 'Customers marked unpaid or overdue.',
            ],
            'suspended' => [
                'label' => 'Suspended',
                'description' => 'Customers currently suspended.',
            ],
            'pending_provisioning' => [
                'label' => 'Pending provisioning',
                'description' => 'Customers still waiting for provisioning.',
            ],
            'paid_subscribers' => [
                'label' => 'Paid subscribers',
                'description' => 'Customers marked paid.',
            ],
        ];
    }

    private function applySegment($query, string $segment): void
    {
        match ($segment) {
            'active_subscribers' => $query->where('connection_status', 'active'),
            'expired_package' => $query->whereNotNull('next_due_date')->whereDate('next_due_date', '<', now()->toDateString()),
            'pending_payment' => $query->whereIn('billing_status', ['unpaid', 'overdue']),
            'suspended' => $query->where('connection_status', 'suspended'),
            'pending_provisioning' => $query->where('provisioning_status', 'pending'),
            'paid_subscribers' => $query->where('billing_status', 'paid'),
            default => null,
        };
    }

    private function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '254') && strlen($digits) === 12) {
            return $digits;
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            return '254' . substr($digits, 1);
        }

        if (strlen($digits) === 9 && preg_match('/^[17]\d{8}$/', $digits)) {
            return '254' . $digits;
        }

        if (strlen($digits) >= 10 && strlen($digits) <= 15) {
            return $digits;
        }

        return null;
    }

    private function personalizeMessage(string $body, Customer $customer): string
    {
        $name = trim((string) $customer->name);
        $parts = preg_split('/\s+/', $name) ?: [];
        $firstName = $parts[0] ?? $name;
        $lastName = count($parts) > 1 ? end($parts) : '';

        $variables = [
            'name' => $name,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => (string) $customer->phone,
            'username' => (string) $customer->username,
            'email' => (string) $customer->email,
            'package_name' => (string) ($customer->internetPackage?->name ?? ''),
            'expiry_date' => (string) $this->dateValue($customer->next_due_date),
            'due_date' => (string) $this->dateValue($customer->next_due_date),
            'monthly_amount' => (string) $customer->monthly_amount,
            'company_name' => (string) ($customer->isp?->name ?? config('app.name')),
        ];

        return (string) preg_replace_callback('/{{\s*([a-zA-Z0-9_]+)\s*}}/', function (array $matches) use ($variables) {
            return $variables[$matches[1]] ?? $matches[0];
        }, $body);
    }

    private function dateValue($value): ?string
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->toDateString();
        }

        return (string) $value;
    }

    private function dateTimeValue($value): ?string
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->format('Y-m-d H:i');
        }

        return (string) $value;
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

}
