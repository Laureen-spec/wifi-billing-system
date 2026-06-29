<?php

namespace StudyRoomTechLab\MpesaPayment\Services;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Order;
use App\Models\Plan;
use App\Models\ProvisioningToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use StudyRoomTechLab\MpesaPayment\Models\IspWallet;
use StudyRoomTechLab\MpesaPayment\Models\IspWalletLedger;
use StudyRoomTechLab\MpesaPayment\Models\MpesaSetting;
use StudyRoomTechLab\MpesaPayment\Models\MpesaTransaction;

class MpesaPaymentService
{
    public function __construct(
        private readonly MpesaDarajaService $daraja
    ) {
    }


    public function initiateStkForPlanSubscription(
        User $user,
        Plan $plan,
        string $duration = 'Month',
        ?string $phone = null,
        ?string $couponCode = null
    ): MpesaTransaction {
        $setting = $this->resolvePlatformSetting();
        $duration = $duration === 'Year' ? 'Year' : 'Month';

        $baseAmount = $duration === 'Year'
            ? (float) ($plan->package_price_yearly ?? 0)
            : (float) ($plan->package_price_monthly ?? 0);

        $invoice = $this->hotspotRevenueInvoiceForUser($user, $plan);
        $hotspotRevenue = (float) $invoice['hotspot_revenue'];
        $hotspotFeePercent = (float) $invoice['hotspot_fee_percent'];
        $hotspotFeeAmount = (float) $invoice['hotspot_fee_amount'];

        $discountAmount = 0.0;
        $subscriptionAmount = $baseAmount;
        $couponCode = trim((string) $couponCode) ?: null;

        if ($couponCode) {
            $validation = applyCouponDiscount($couponCode, $baseAmount, $user->id);

            if (! ($validation['valid'] ?? false)) {
                throw new \RuntimeException($validation['message'] ?? 'Invalid coupon code.');
            }

            $discountAmount = (float) ($validation['discount_amount'] ?? 0);
            $subscriptionAmount = (float) ($validation['final_amount'] ?? max(0, $baseAmount - $discountAmount));
        }

        $finalAmount = max(0, $subscriptionAmount) + $hotspotFeeAmount;

        if ($finalAmount <= 0) {
            throw new \RuntimeException('M-Pesa cannot process a zero balance subscription. Please use a free plan or contact support.');
        }

        if (empty($phone)) {
            throw new \RuntimeException('Admin M-Pesa phone number is required for subscription payment.');
        }

        $modules = is_array($plan->modules) ? $plan->modules : (json_decode($plan->modules ?? '[]', true) ?: []);
        $orderId = strtoupper(substr(uniqid('MP'), -12));
        $metadata = [
            'kind' => 'plan_subscription',
            'order_id' => $orderId,
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'duration' => $duration,
            'user_module' => implode(',', $modules),
            'counter' => [
                'user_counter' => $plan->number_of_users ?? 0,
                'storage_limit' => 0,
            ],
            'coupon_code' => $couponCode,
            'base_amount' => $baseAmount,
            'discount_amount' => $discountAmount,
            'subscription_amount' => $subscriptionAmount,
            'hotspot_revenue' => $hotspotRevenue,
            'hotspot_fee_percent' => $hotspotFeePercent,
            'hotspot_fee_amount' => $hotspotFeeAmount,
            'final_amount' => $finalAmount,
            'invoice_lines' => [
                ['label' => 'Base subscription', 'amount' => $baseAmount],
                ['label' => 'Coupon discount', 'amount' => -1 * $discountAmount],
                ['label' => 'Hotspot revenue generated', 'amount' => $hotspotRevenue, 'display_only' => true],
                ['label' => 'Hotspot revenue fee (' . $hotspotFeePercent . '%)', 'amount' => $hotspotFeeAmount],
            ],
        ];

        $transaction = MpesaTransaction::create([
            'isp_id' => $user->id,
            'customer_id' => null,
            'internet_package_id' => null,
            'mikrotik_router_id' => null,
            'mpesa_setting_id' => $setting->id,
            'collection_mode' => 'platform',
            'environment' => $setting->environment,
            'payment_type' => 'plan_subscription',
            'phone' => $this->daraja->normalizePhone($phone),
            'amount' => $finalAmount,
            'currency' => 'KES',
            'account_reference' => $orderId,
            'transaction_desc' => 'ISP plan subscription and hotspot revenue fee',
            'status' => 'pending',
            'created_by' => $user->id,
            'request_payload' => [
                'plan_subscription' => $metadata,
            ],
        ]);

        try {
            $result = $this->daraja->stkPush(
                $setting,
                $transaction->phone,
                (float) $transaction->amount,
                $transaction->account_reference ?: $orderId,
                $transaction->transaction_desc ?: 'ISP plan subscription and hotspot revenue fee'
            );

            $transaction->forceFill([
                'request_payload' => [
                    'plan_subscription' => $metadata,
                    'daraja_payload' => $result['payload'],
                ],
            ])->save();

            $transaction->markStkSent($result['response']);

            return $transaction;
        } catch (\Throwable $e) {
            $transaction->forceFill([
                'status' => 'failed',
                'result_desc' => $e->getMessage(),
                'failed_at' => now(),
            ])->save();

            throw $e;
        }
    }

    public function initiateStkForCustomer(
        Customer $customer,
        ?int $internetPackageId = null,
        ?float $amount = null,
        ?string $phone = null,
        ?int $userId = null
    ): MpesaTransaction {
        $package = InternetPackage::find($internetPackageId ?: ($customer->internet_package_id ?? null));

        $setting = $this->resolveSetting((int) ($customer->isp_id ?? 0));

        $amount = $amount ?: $this->resolveAmount($customer, $package);

        $phone = $phone ?: (
            $customer->phone
            ?? $customer->phone_number
            ?? $customer->mobile
            ?? $customer->mobile_number
            ?? $customer->username
            ?? null
        );

        if ($amount <= 0) {
            throw new \RuntimeException('Payment amount must be greater than zero.');
        }

        if (empty($phone)) {
            throw new \RuntimeException('Customer phone number is required for M-Pesa STK Push.');
        }

        $transaction = MpesaTransaction::create([
            'isp_id' => $customer->isp_id ?? null,
            'customer_id' => $customer->id,
            'internet_package_id' => $package?->id ?: $internetPackageId,
            'mikrotik_router_id' => $customer->mikrotik_router_id ?? null,
            'mpesa_setting_id' => $setting->id,
            'collection_mode' => $setting->collection_mode,
            'environment' => $setting->environment,
            'payment_type' => 'stk_push',
            'phone' => $this->daraja->normalizePhone($phone),
            'amount' => $amount,
            'currency' => 'KES',
            'account_reference' => $setting->account_reference ?: 'StudyRoom WiFi',
            'transaction_desc' => config('mpesa-payment.transaction_desc', 'WiFi subscription payment'),
            'status' => 'pending',
            'created_by' => $userId,
        ]);

        try {
            $result = $this->daraja->stkPush(
                $setting,
                $transaction->phone,
                (float) $transaction->amount,
                $transaction->account_reference ?: 'StudyRoom WiFi',
                $transaction->transaction_desc ?: 'WiFi subscription payment'
            );

            $transaction->forceFill([
                'request_payload' => $result['payload'],
            ])->save();

            $transaction->markStkSent($result['response']);

            return $transaction;
        } catch (\Throwable $e) {
            $transaction->forceFill([
                'status' => 'failed',
                'result_desc' => $e->getMessage(),
                'failed_at' => now(),
            ])->save();

            throw $e;
        }
    }

    public function handleCallback(array $payload): ?MpesaTransaction
    {
        $callback = data_get($payload, 'Body.stkCallback');

        if (! $callback) {
            Log::warning('Invalid M-Pesa callback payload', ['payload' => $payload]);
            return null;
        }

        $checkoutRequestId = $callback['CheckoutRequestID'] ?? null;
        $resultCode = (int) ($callback['ResultCode'] ?? 1);
        $resultDesc = (string) ($callback['ResultDesc'] ?? '');

        if (! $checkoutRequestId) {
            Log::warning('M-Pesa callback missing CheckoutRequestID', ['payload' => $payload]);
            return null;
        }

        $transaction = MpesaTransaction::where('checkout_request_id', $checkoutRequestId)->first();

        if (! $transaction) {
            Log::warning('M-Pesa transaction not found for callback', [
                'checkout_request_id' => $checkoutRequestId,
            ]);
            return null;
        }

        if ($resultCode !== 0) {
            $transaction->markFailed($payload, $resultCode, $resultDesc);
            return $transaction;
        }

        $receipt = $this->callbackItem($callback, 'MpesaReceiptNumber');
        $paidAmount = $this->callbackItem($callback, 'Amount');
        $phone = $this->callbackItem($callback, 'PhoneNumber');

        return DB::transaction(function () use ($transaction, $payload, $receipt, $paidAmount, $phone) {
            $transaction->forceFill([
                'amount' => $paidAmount ?: $transaction->amount,
                'phone' => $phone ?: $transaction->phone,
            ])->save();

            $transaction->markPaid($payload, $receipt);

            if ($transaction->payment_type === 'plan_subscription') {
                $this->activatePlanSubscriptionIfNeeded($transaction);
                return $transaction->refresh();
            }

            $this->postWalletIfNeeded($transaction);
            $this->triggerProvisioningIfNeeded($transaction);

            return $transaction->refresh();
        });
    }

    public function simulatePaid(MpesaTransaction $transaction): MpesaTransaction
    {
        abort_unless(config('app.debug'), 403, 'Simulation is only allowed when APP_DEBUG=true.');

        return DB::transaction(function () use ($transaction) {
            $receipt = 'SIM' . now()->format('YmdHis') . random_int(100, 999);

            $transaction->forceFill([
                'status' => 'paid',
                'result_code' => 0,
                'result_desc' => 'Simulated payment success.',
                'mpesa_receipt_number' => $receipt,
                'paid_at' => now(),
                'callback_payload' => [
                    'simulation' => true,
                    'receipt' => $receipt,
                    'paid_at' => now()->toDateTimeString(),
                ],
            ])->save();

            if ($transaction->payment_type === 'plan_subscription') {
                $this->activatePlanSubscriptionIfNeeded($transaction);
            } else {
                $this->postWalletIfNeeded($transaction);
                $this->triggerProvisioningIfNeeded($transaction);
            }

            return $transaction->refresh();
        });
    }

    public function resolveSetting(?int $ispId = null): MpesaSetting
    {
        if ($ispId) {
            $ispSetting = MpesaSetting::where('isp_id', $ispId)
                ->where('owner_type', 'isp')
                ->where('collection_mode', 'isp_direct')
                ->where('is_active', true)
                ->first();

            if ($ispSetting) {
                return $ispSetting;
            }
        }

        $platformSetting = MpesaSetting::whereNull('isp_id')
            ->where('owner_type', 'platform')
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->first();

        if (! $platformSetting) {
            throw new \RuntimeException('No active platform M-Pesa setting found.');
        }

        return $platformSetting;
    }

    private function postWalletIfNeeded(MpesaTransaction $transaction): void
    {
        if ($transaction->wallet_posted || ! $transaction->isp_id) {
            return;
        }

        $setting = $transaction->setting ?: MpesaSetting::find($transaction->mpesa_setting_id);

        $amount = (float) $transaction->amount;
        $platformFee = $this->calculateCommission($amount, $setting);
        $ispAmount = max(0, $amount - $platformFee);

        $transaction->forceFill([
            'platform_fee' => $platformFee,
            'isp_amount' => $setting?->collection_mode === 'isp_direct' ? $amount : $ispAmount,
            'wallet_posted' => true,
        ])->save();

        if ($setting?->collection_mode === 'isp_direct') {
            return;
        }

        $wallet = IspWallet::firstOrCreate(
            ['isp_id' => $transaction->isp_id],
            [
                'available_balance' => 0,
                'pending_balance' => 0,
                'total_earned' => 0,
                'total_paid_out' => 0,
                'total_platform_fee' => 0,
                'currency' => 'KES',
                'is_active' => true,
            ]
        );

        $before = (float) $wallet->available_balance;
        $after = $before + $ispAmount;

        $wallet->forceFill([
            'available_balance' => $after,
            'total_earned' => (float) $wallet->total_earned + $ispAmount,
            'total_platform_fee' => (float) $wallet->total_platform_fee + $platformFee,
        ])->save();

        IspWalletLedger::create([
            'isp_id' => $transaction->isp_id,
            'isp_wallet_id' => $wallet->id,
            'type' => 'credit',
            'source' => 'mpesa_payment',
            'mpesa_transaction_id' => $transaction->id,
            'customer_id' => $transaction->customer_id,
            'amount' => $ispAmount,
            'balance_before' => $before,
            'balance_after' => $after,
            'currency' => 'KES',
            'reference' => $transaction->mpesa_receipt_number,
            'description' => 'M-Pesa customer payment credited to ISP wallet.',
        ]);

        if ($platformFee > 0) {
            IspWalletLedger::create([
                'isp_id' => $transaction->isp_id,
                'isp_wallet_id' => $wallet->id,
                'type' => 'platform_fee',
                'source' => 'mpesa_payment',
                'mpesa_transaction_id' => $transaction->id,
                'customer_id' => $transaction->customer_id,
                'amount' => $platformFee,
                'balance_before' => $after,
                'balance_after' => $after,
                'currency' => 'KES',
                'reference' => $transaction->mpesa_receipt_number,
                'description' => 'Platform commission retained by Super Admin.',
            ]);
        }
    }

    private function triggerProvisioningIfNeeded(MpesaTransaction $transaction): void
    {
        if ($transaction->provisioning_triggered || ! $transaction->customer_id) {
            return;
        }

        $customer = Customer::find($transaction->customer_id);

        if (! $customer) {
            return;
        }

        $table = $customer->getTable();
        $updates = [];

        if (Schema::hasColumn($table, 'billing_status')) {
            $updates['billing_status'] = 'paid';
        }

        if (Schema::hasColumn($table, 'connection_status')) {
            $updates['connection_status'] = 'active';
        }

        if (Schema::hasColumn($table, 'status')) {
            $updates['status'] = 'active';
        }

        if (Schema::hasColumn($table, 'internet_package_id') && $transaction->internet_package_id) {
            $updates['internet_package_id'] = $transaction->internet_package_id;
        }

        if (Schema::hasColumn($table, 'package_id') && $transaction->internet_package_id) {
            $updates['package_id'] = $transaction->internet_package_id;
        }

        if (Schema::hasColumn($table, 'monthly_amount')) {
            $updates['monthly_amount'] = $transaction->amount;
        }

        if (! empty($updates)) {
            $customer->forceFill($updates)->save();
        }

        if (class_exists(\StudyRoomTechLab\WifiBilling\Services\CustomerAutoProvisioningService::class)) {
            $service = app(\StudyRoomTechLab\WifiBilling\Services\CustomerAutoProvisioningService::class);
            $service->retryCustomer($customer->refresh(), $transaction->created_by);
        }

        $token = ProvisioningToken::where('customer_id', $customer->id)
            ->latest()
            ->first();

        if ($token && $transaction->internet_package_id && empty($token->internet_package_id)) {
            $token->forceFill([
                'internet_package_id' => $transaction->internet_package_id,
            ])->save();
        }

        $transaction->forceFill([
            'provisioning_triggered' => true,
            'provisioning_token_id' => $token?->id,
            'provisioned_at' => now(),
        ])->save();
    }


    public function resolvePlatformSetting(): MpesaSetting
    {
        $platformSetting = MpesaSetting::whereNull('isp_id')
            ->where('owner_type', 'platform')
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->first();

        if (! $platformSetting) {
            throw new \RuntimeException('Payment gateway is not configured. Please contact platform support.');
        }

        return $platformSetting;
    }

    private function hotspotRevenueInvoiceForUser(User $user, Plan $plan): array
    {
        $tenantIds = array_values(array_unique(array_filter([
            (int) $user->id,
            (int) ($user->created_by ?: $user->id),
        ])));

        $hotspotRevenue = 0.0;

        if (Schema::hasTable('mpesa_transactions')) {
            $query = DB::table('mpesa_transactions')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);

            $query->where(function ($ownerQuery) use ($tenantIds) {
                if (Schema::hasColumn('mpesa_transactions', 'created_by')) {
                    $ownerQuery->orWhereIn('created_by', $tenantIds);
                }
                if (Schema::hasColumn('mpesa_transactions', 'company_id')) {
                    $ownerQuery->orWhereIn('company_id', $tenantIds);
                }
                if (Schema::hasColumn('mpesa_transactions', 'user_id')) {
                    $ownerQuery->orWhereIn('user_id', $tenantIds);
                }
                if (Schema::hasColumn('mpesa_transactions', 'isp_id')) {
                    $ownerQuery->orWhereIn('isp_id', $tenantIds);
                }
            });

            if (Schema::hasColumn('mpesa_transactions', 'status')) {
                $query->whereIn('status', ['paid', 'success', 'successful', 'completed', 'succeeded']);
            }

            if (Schema::hasColumn('mpesa_transactions', 'payment_type')) {
                $query->where(function ($q) {
                    $q->whereNull('payment_type')
                        ->orWhere('payment_type', '!=', 'plan_subscription');
                });
            }

            if (Schema::hasColumn('mpesa_transactions', 'customer_id')) {
                $query->whereNotNull('customer_id');
            }

            $hotspotRevenue = (float) $query->sum('amount');
        }

        $feePercent = $this->planHotspotFee($plan);
        $feeAmount = round($hotspotRevenue * ($feePercent / 100), 2);

        return [
            'hotspot_revenue' => $hotspotRevenue,
            'hotspot_fee_percent' => $feePercent,
            'hotspot_fee_amount' => $feeAmount,
        ];
    }

    private function planHotspotFee(?Plan $plan): float
    {
        if ($plan && isset($plan->hotspot_revenue_fee_percent)) {
            return (float) $plan->hotspot_revenue_fee_percent;
        }

        return 2.5;
    }

    private function activatePlanSubscriptionIfNeeded(MpesaTransaction $transaction): void
    {
        if ($transaction->payment_type !== 'plan_subscription' || ! $transaction->isPaid()) {
            return;
        }

        $metadata = data_get($transaction->request_payload, 'plan_subscription', []);
        $orderId = $metadata['order_id'] ?? $transaction->account_reference;

        if ($orderId && Order::where('order_id', $orderId)->exists()) {
            return;
        }

        if ($transaction->mpesa_receipt_number && Order::where('txn_id', $transaction->mpesa_receipt_number)->exists()) {
            return;
        }

        $plan = Plan::find($metadata['plan_id'] ?? null);
        $user = User::find($metadata['user_id'] ?? $transaction->created_by);

        if (! $plan || ! $user) {
            Log::warning('M-Pesa plan subscription paid but plan/user was not found.', [
                'transaction_id' => $transaction->id,
                'metadata' => $metadata,
            ]);
            return;
        }

        $duration = ($metadata['duration'] ?? 'Month') === 'Year' ? 'Year' : 'Month';
        $userModule = (string) ($metadata['user_module'] ?? '');
        $counter = is_array($metadata['counter'] ?? null)
            ? $metadata['counter']
            : [
                'user_counter' => $plan->number_of_users ?? 0,
                'storage_limit' => 0,
            ];

        $assignPlan = assignPlan($plan->id, $duration, $userModule, $counter, $user->id);

        if (! ($assignPlan['is_success'] ?? false)) {
            Log::error('M-Pesa plan subscription paid but plan assignment failed.', [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'error' => $assignPlan['error'] ?? null,
            ]);
            return;
        }

        Order::create([
            'order_id' => $orderId ?: strtoupper(substr(uniqid('MP'), -12)),
            'name' => $user->name,
            'email' => $user->email,
            'card_number' => null,
            'card_exp_month' => null,
            'card_exp_year' => null,
            'plan_name' => ! empty($plan->name) ? $plan->name : 'ISP Package',
            'plan_id' => $plan->id,
            'price' => $transaction->amount,
            'discount_amount' => (float) ($metadata['discount_amount'] ?? 0),
            'currency' => $transaction->currency ?: 'KES',
            'txn_id' => $transaction->mpesa_receipt_number ?: $transaction->checkout_request_id,
            'payment_type' => 'M-Pesa',
            'payment_status' => 'succeeded',
            'receipt' => $transaction->mpesa_receipt_number,
            'created_by' => $user->id,
        ]);

        if (! empty($metadata['coupon_code']) && class_exists(\App\Models\Coupon::class)) {
            $coupon = \App\Models\Coupon::where('code', $metadata['coupon_code'])->first();
            if ($coupon) {
                recordCouponUsage($coupon->id, $user->id, $orderId);
            }
        }
    }

    private function resolveAmount(Customer $customer, ?InternetPackage $package): float
    {
        foreach (['price', 'amount', 'monthly_price', 'monthly_amount', 'selling_price'] as $field) {
            if ($package && isset($package->{$field}) && (float) $package->{$field} > 0) {
                return (float) $package->{$field};
            }
        }

        foreach (['monthly_amount', 'amount', 'price'] as $field) {
            if (isset($customer->{$field}) && (float) $customer->{$field} > 0) {
                return (float) $customer->{$field};
            }
        }

        return 0;
    }

    private function calculateCommission(float $amount, ?MpesaSetting $setting): float
    {
        if (! $setting || $setting->commission_type === 'none') {
            return 0;
        }

        if ($setting->commission_type === 'fixed') {
            return min($amount, (float) $setting->commission_value);
        }

        return round($amount * ((float) $setting->commission_value / 100), 2);
    }

    private function callbackItem(array $callback, string $name): mixed
    {
        $items = data_get($callback, 'CallbackMetadata.Item', []);

        foreach ($items as $item) {
            if (($item['Name'] ?? null) === $name) {
                return $item['Value'] ?? null;
            }
        }

        return null;
    }
}