<?php

namespace StudyRoomTechLab\IspSms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
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
            'subtitle' => 'Generate an SMS wallet invoice for system SMS usage.',
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
        abort_unless(Schema::hasTable('isp_sms_settings'), 500, 'SMS settings table is not migrated yet.');
        abort_unless(Schema::hasTable('isp_sms_topups'), 500, 'SMS top-up table is not migrated yet.');

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:10', 'max:1000000'],
            'payment_method' => ['nullable', 'string', 'max:40'],
        ]);

        $isp = $this->resolveIsp($request);
        $user = $request->user();
        $setting = IspSmsSetting::firstOrCreate(
            ['scope' => 'isp', 'isp_id' => $isp->id],
            [
                'mode' => 'platform',
                'provider' => 'platform',
                'is_active' => true,
                'allow_system_sms' => true,
                'allow_own_sms' => true,
                'free_sms_remaining' => 5,
                'sms_balance' => 0,
                'estimated_cost_per_sms' => 1,
            ]
        );

        $amount = round((float) $data['amount'], 2);
        $cost = max((float) ($setting->estimated_cost_per_sms ?? 1), 0.01);
        $units = (int) floor($amount / $cost);
        $currency = $this->currency();
        $orderId = 'SMS-' . now()->format('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $topupNumber = 'STU-' . now()->format('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

        DB::transaction(function () use ($isp, $user, $amount, $currency, $units, $orderId, $topupNumber, $data) {
            Order::create([
                'order_id' => $orderId,
                'name' => $user->name,
                'email' => $user->email,
                'card_number' => null,
                'card_exp_month' => null,
                'card_exp_year' => null,
                'plan_name' => 'SMS Wallet Top-up',
                'plan_id' => null,
                'price' => $amount,
                'discount_amount' => 0,
                'currency' => $currency,
                'txn_id' => null,
                'payment_type' => $data['payment_method'] ?? 'SMS Checkout',
                'payment_status' => 'pending',
                'receipt' => json_encode([
                    'invoice_type' => 'sms_topup',
                    'topup_number' => $topupNumber,
                    'expense_category' => 'sms_topup',
                    'sms_units' => $units,
                ]),
                'created_by' => $user->id,
            ]);

            IspSmsTopup::create([
                'isp_id' => $isp->id,
                'user_id' => $user->id,
                'topup_number' => $topupNumber,
                'order_id' => $orderId,
                'amount' => $amount,
                'currency' => $currency,
                'sms_units' => $units,
                'payment_method' => $data['payment_method'] ?? 'checkout',
                'status' => 'pending',
                'metadata' => [
                    'invoice_generated' => true,
                    'expense_category' => 'sms_topup',
                    'note' => 'SMS wallet top-up invoice generated. Balance is credited after payment approval.',
                ],
            ]);

            $this->createExpenseRecordIfAvailable($isp->id, $user->id, $topupNumber, $amount, $data['payment_method'] ?? 'checkout');
        });

        return redirect()
            ->route('isp.sms.topup')
            ->with('success', 'SMS top-up invoice generated. Complete payment for the SMS balance to be credited.');
    }

    private function createExpenseRecordIfAvailable(int $ispId, int $userId, string $receipt, float $amount, string $paymentMethod): void
    {
        if (! Schema::hasTable('isp_expenses')) {
            return;
        }

        DB::table('isp_expenses')->insert([
            'isp_id' => $ispId,
            'expense_number' => 'EXP-SMS-' . now()->format('YmdHis') . '-' . random_int(100, 999),
            'category' => 'sms_topup',
            'description' => 'System SMS wallet top-up',
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'receipt_number' => $receipt,
            'expense_date' => now()->toDateString(),
            'status' => 'pending',
            'notes' => 'Generated from SMS top-up checkout. Mark paid when checkout payment is confirmed.',
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
}
