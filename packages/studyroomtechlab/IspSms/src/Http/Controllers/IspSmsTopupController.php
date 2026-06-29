<?php

namespace StudyRoomTechLab\IspSms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\IspTenantResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use StudyRoomTechLab\IspSms\Models\IspSmsSetting;
use StudyRoomTechLab\IspSms\Models\IspSmsTopup;

class IspSmsTopupController extends Controller
{
    public function create(Request $request)
    {
        $this->authorizeManage($request);

        $isp = $this->resolveIsp($request);
        $setting = Schema::hasTable('isp_sms_settings')
            ? IspSmsSetting::where('scope', 'isp')->where('isp_id', $isp->id)->first()
            : null;

        $recent = [];
        if (Schema::hasTable('isp_sms_topups')) {
            $recent = IspSmsTopup::query()
                ->where('isp_id', $isp->id)
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (IspSmsTopup $topup) => [
                    'id' => $topup->id,
                    'topup_number' => $topup->topup_number,
                    'order_id' => $topup->order_id,
                    'amount' => (float) $topup->amount,
                    'currency' => $topup->currency,
                    'sms_units' => (int) $topup->sms_units,
                    'status' => $topup->status,
                    'created_at' => optional($topup->created_at)->format('Y-m-d H:i'),
                ])
                ->values();
        }

        return Inertia::render('sms/topup', [
            'pageTitle' => 'SMS Top-up Checkout',
            'subtitle' => 'Pay for system SMS credits. Your balance is credited after Super Admin approval or successful online payment confirmation.',
            'wallet' => [
                'balance' => (float) ($setting?->sms_balance ?? 0),
                'free_sms_remaining' => (int) ($setting?->free_sms_remaining ?? 5),
                'estimated_cost_per_sms' => (float) ($setting?->estimated_cost_per_sms ?? 1),
                'low_balance_alert_threshold' => (float) ($setting?->low_balance_alert_threshold ?? 10),
            ],
            'recentTopups' => $recent,
            'routes' => [
                'settings' => route('isp.sms.settings'),
                'store' => route('isp.sms.topup.store'),
                'messages' => route('isp.sms.index'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeManage($request);

        if (! Schema::hasTable('isp_sms_settings') || ! Schema::hasTable('isp_sms_topups')) {
            return back()
                ->with('error', 'SMS top-up is not ready. Please run migrations, then try again.')
                ->withErrors(['amount' => 'SMS top-up tables are missing. Run php artisan migrate.'])
                ->withInput();
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:10', 'max:1000000'],
            'payment_method' => ['required', 'string', 'in:mpesa,manual'],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);

        if (($data['payment_method'] ?? null) === 'mpesa' && empty($data['phone'])) {
            return back()
                ->withErrors(['phone' => 'Enter the M-Pesa phone number that will receive the payment prompt.'])
                ->withInput();
        }

        try {
            $isp = $this->resolveIsp($request);
            $user = $request->user();
            $setting = $this->safeIspSetting((int) $isp->id, (int) $user->id);

            $amount = round((float) $data['amount'], 2);
            $cost = max((float) ($setting->estimated_cost_per_sms ?? 1), 0.01);
            $units = (int) floor($amount / $cost);
            $currency = $this->currency();
            $topupNumber = 'STU-' . now()->format('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

            $topup = $this->safeCreateTopup([
                'isp_id' => $isp->id,
                'user_id' => $user->id,
                'topup_number' => $topupNumber,
                'order_id' => null,
                'amount' => $amount,
                'currency' => $currency,
                'sms_units' => $units,
                'payment_method' => $data['payment_method'],
                'status' => $data['payment_method'] === 'mpesa' ? 'pending_mpesa' : 'pending_approval',
                'paid_at' => null,
                'metadata' => [
                    'payment_method' => $data['payment_method'],
                    'phone' => $data['phone'] ?? null,
                    'invoice_generated' => false,
                    'invoice_after_payment_confirmation' => true,
                    'expense_after_payment_confirmation' => true,
                ],
            ]);

            return redirect()
                ->route('isp.sms.topup')
                ->with('success', $topup->payment_method === 'mpesa'
                    ? 'SMS top-up payment request created. Confirm M-Pesa payment to credit SMS balance.'
                    : 'SMS top-up request created. Super Admin must approve payment before SMS balance is credited.');
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', 'SMS top-up could not be started. Please check SMS settings and migrations, then try again.')
                ->withErrors(['amount' => $e->getMessage() ?: 'SMS top-up failed.'])
                ->withInput();
        }
    }

    public function approve(Request $request, IspSmsTopup $topup)
    {
        $this->authorizePlatform($request);

        $this->confirmTopupPayment($topup, [
            'confirmed_by' => $request->user()->id,
            'confirmation_method' => 'manual_super_admin',
        ]);

        return back()->with('success', 'SMS top-up approved. SMS balance was credited and invoice was generated.');
    }

    private function safeIspSetting(int $ispId, int $userId): IspSmsSetting
    {
        $setting = IspSmsSetting::where('scope', 'isp')->where('isp_id', $ispId)->first();

        if ($setting) {
            return $setting;
        }

        $columns = Schema::getColumnListing('isp_sms_settings');
        $payload = [
            'scope' => 'isp',
            'isp_id' => $ispId,
            'mode' => 'platform',
            'provider' => 'platform',
            'is_active' => true,
            'allow_system_sms' => true,
            'allow_own_sms' => true,
            'free_sms_remaining' => 5,
            'sms_balance' => 0,
            'estimated_cost_per_sms' => 1,
            'low_balance_alert_enabled' => true,
            'low_balance_alert_threshold' => 10,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('isp_sms_settings')->insert(array_intersect_key($payload, array_flip($columns)));

        return IspSmsSetting::where('scope', 'isp')->where('isp_id', $ispId)->firstOrFail();
    }

    private function safeCreateTopup(array $payload): IspSmsTopup
    {
        $columns = Schema::getColumnListing('isp_sms_topups');
        $insert = $payload;
        $insert['created_at'] = now();
        $insert['updated_at'] = now();

        if (array_key_exists('metadata', $insert) && in_array('metadata', $columns, true) && is_array($insert['metadata'])) {
            $insert['metadata'] = json_encode($insert['metadata']);
        }

        $id = DB::table('isp_sms_topups')->insertGetId(array_intersect_key($insert, array_flip($columns)));

        return IspSmsTopup::findOrFail($id);
    }

    private function confirmTopupPayment(IspSmsTopup $topup, array $confirmation = []): IspSmsTopup
    {
        if (in_array($topup->status, ['paid', 'approved'], true)) {
            return $topup->refresh();
        }

        return DB::transaction(function () use ($topup, $confirmation) {
            $setting = $this->safeIspSetting((int) $topup->isp_id, (int) ($topup->user_id ?? 0));

            $setting->sms_balance = round((float) ($setting->sms_balance ?? 0) + (float) $topup->amount, 2);
            $setting->save();

            $orderId = $topup->order_id ?: 'SMS-' . now()->format('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

            $this->createOrderRecordIfAvailable($topup, $orderId, $confirmation);
            $this->createExpenseRecordIfAvailable((int) $topup->isp_id, (int) ($topup->user_id ?? 0), $topup->topup_number, (float) $topup->amount, (string) $topup->payment_method);

            $metadata = $topup->metadata ?: [];
            $metadata['invoice_generated'] = true;
            $metadata['expense_generated'] = Schema::hasTable('isp_expenses');
            $metadata['payment_confirmation'] = $confirmation;

            $topup->forceFill([
                'order_id' => $orderId,
                'status' => 'paid',
                'paid_at' => now(),
                'metadata' => $metadata,
            ])->save();

            return $topup->refresh();
        });
    }

    private function createOrderRecordIfAvailable(IspSmsTopup $topup, string $orderId, array $confirmation = []): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        $columns = Schema::getColumnListing('orders');

        if (! in_array('order_id', $columns, true)) {
            return;
        }

        if (DB::table('orders')->where('order_id', $orderId)->exists()) {
            return;
        }

        $user = $topup->user;
        $payload = [
            'order_id' => $orderId,
            'name' => $user?->name ?: 'SMS Top-up',
            'email' => $user?->email,
            'plan_name' => 'SMS Wallet Top-up',
            'plan_id' => null,
            'price' => (float) $topup->amount,
            'discount_amount' => 0,
            'currency' => $topup->currency ?: $this->currency(),
            'txn_id' => $confirmation['transaction_id'] ?? $confirmation['mpesa_receipt'] ?? null,
            'payment_type' => $topup->payment_method,
            'payment_status' => 'paid',
            'receipt' => json_encode([
                'invoice_type' => 'sms_topup',
                'topup_number' => $topup->topup_number,
                'expense_category' => 'sms_topup',
                'sms_units' => $topup->sms_units,
                'confirmation' => $confirmation,
            ]),
            'created_by' => $topup->user_id,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('orders')->insert(array_intersect_key($payload, array_flip($columns)));
    }

    private function createExpenseRecordIfAvailable(int $ispId, int $userId, string $receipt, float $amount, string $paymentMethod): void
    {
        if (! Schema::hasTable('isp_expenses')) {
            return;
        }

        $columns = Schema::getColumnListing('isp_expenses');
        $payload = [
            'isp_id' => $ispId,
            'expense_number' => 'EXP-SMS-' . now()->format('YmdHis') . '-' . random_int(100, 999),
            'category' => 'sms_topup',
            'description' => 'System SMS wallet top-up',
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'receipt_number' => $receipt,
            'expense_date' => now()->toDateString(),
            'status' => 'paid',
            'notes' => 'Generated after SMS top-up payment confirmation.',
            'created_by' => $userId ?: null,
            'updated_by' => $userId ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('isp_expenses')->insert(array_intersect_key($payload, array_flip($columns)));
    }

    private function currency(): string
    {
        try {
            if (function_exists('admin_setting')) {
                return (string) (admin_setting('defaultCurrency') ?: 'KES');
            }
        } catch (\Throwable) {
            // keep fallback
        }

        return 'KES';
    }

    private function resolveIsp(Request $request)
    {
        return app(IspTenantResolver::class)->resolve($request);
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless(
            app(IspTenantResolver::class)->isPlatform($request)
            || $request->user()->can('manage-wifi-dashboard')
            || $request->user()->can('manage-isp-customers'),
            403
        );
    }

    private function authorizePlatform(Request $request): void
    {
        abort_unless(app(IspTenantResolver::class)->isPlatform($request), 403);
    }
}
