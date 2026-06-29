<?php

namespace StudyRoomTechLab\MpesaPayment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MpesaTransaction extends Model
{
    protected $table = 'mpesa_transactions';

    protected $fillable = [
        'isp_id',
        'customer_id',
        'internet_package_id',
        'mikrotik_router_id',
        'mpesa_setting_id',
        'collection_mode',
        'environment',
        'payment_type',
        'phone',
        'amount',
        'currency',
        'merchant_request_id',
        'checkout_request_id',
        'mpesa_receipt_number',
        'account_reference',
        'transaction_desc',
        'status',
        'result_code',
        'result_desc',
        'request_payload',
        'response_payload',
        'callback_payload',
        'platform_fee',
        'isp_amount',
        'wallet_posted',
        'provisioning_token_id',
        'provisioning_triggered',
        'provisioned_at',
        'paid_at',
        'failed_at',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'isp_amount' => 'decimal:2',
        'wallet_posted' => 'boolean',
        'provisioning_triggered' => 'boolean',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'callback_payload' => 'array',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'provisioned_at' => 'datetime',
    ];

    public function setting(): BelongsTo
    {
        return $this->belongsTo(MpesaSetting::class, 'mpesa_setting_id');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'stk_sent'], true);
    }

    public function isFailed(): bool
    {
        return in_array($this->status, ['failed', 'cancelled', 'expired'], true);
    }

    public function markStkSent(array $response): void
    {
        $this->forceFill([
            'status' => 'stk_sent',
            'merchant_request_id' => $response['MerchantRequestID'] ?? $this->merchant_request_id,
            'checkout_request_id' => $response['CheckoutRequestID'] ?? $this->checkout_request_id,
            'response_payload' => $response,
        ])->save();
    }

    public function markPaid(array $callback, ?string $receipt = null): void
    {
        $this->forceFill([
            'status' => 'paid',
            'mpesa_receipt_number' => $receipt ?: $this->mpesa_receipt_number,
            'callback_payload' => $callback,
            'paid_at' => now(),
        ])->save();
    }

    public function markFailed(array $callback, ?int $code = null, ?string $message = null): void
    {
        $this->forceFill([
            'status' => 'failed',
            'result_code' => $code,
            'result_desc' => $message,
            'callback_payload' => $callback,
            'failed_at' => now(),
        ])->save();
    }
}
