<?php

namespace StudyRoomTechLab\MpesaPayment\Services;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\ProvisioningToken;
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

    public function initiateStkForCustomer(
        Customer $customer,
        ?int $internetPackageId = null,
        ?float $amount = null,
        ?string $phone = null,
        ?int $userId = null
    ): MpesaTransaction {
        $package = InternetPackage::find($internetPackageId ?: ($customer->internet_package_id ?? null));

        $ispId = (int) ($customer->isp_id ?? 0);
        $setting = $this->resolveSetting($ispId);

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
            'account_reference' => $this->resolveAccountReference($ispId, $setting),
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

            $this->postWalletIfNeeded($transaction);
            $this->triggerProvisioningIfNeeded($transaction);

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


    private function resolveAccountReference(?int $ispId, MpesaSetting $setting): string
    {
        if ($setting->collection_mode === 'isp_direct' || ! $ispId) {
            return $setting->account_reference ?: 'StudyRoom WiFi';
        }

        $ispSystemSetting = MpesaSetting::where('isp_id', $ispId)
            ->where('owner_type', 'isp')
            ->where('collection_mode', 'platform')
            ->where('is_active', true)
            ->latest('updated_at')
            ->first();

        if (! $ispSystemSetting) {
            return $setting->account_reference ?: 'StudyRoom WiFi';
        }

        if ($ispSystemSetting->account_reference) {
            return $ispSystemSetting->account_reference;
        }

        return match ($ispSystemSetting->system_payment_channel) {
            'paybill' => trim(($ispSystemSetting->system_paybill_number ?: '') . ' ' . ($ispSystemSetting->system_account_number ?: '')) ?: ($setting->account_reference ?: 'StudyRoom WiFi'),
            'phone' => $ispSystemSetting->system_phone_number ?: ($setting->account_reference ?: 'StudyRoom WiFi'),
            'till' => $ispSystemSetting->system_till_number ?: ($setting->account_reference ?: 'StudyRoom WiFi'),
            default => $setting->account_reference ?: 'StudyRoom WiFi',
        };
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