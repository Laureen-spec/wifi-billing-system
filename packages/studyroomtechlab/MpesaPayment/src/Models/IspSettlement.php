<?php

namespace StudyRoomTechLab\MpesaPayment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IspSettlement extends Model
{
    protected $table = 'isp_settlements';

    protected $fillable = [
        'isp_id',
        'isp_wallet_id',
        'settlement_number',
        'amount',
        'fee',
        'net_amount',
        'currency',
        'payout_method',
        'payout_name',
        'payout_phone',
        'payout_shortcode',
        'status',
        'mpesa_receipt_number',
        'transaction_reference',
        'notes',
        'failure_reason',
        'request_payload',
        'response_payload',
        'metadata',
        'requested_by',
        'approved_by',
        'processed_by',
        'requested_at',
        'approved_at',
        'processed_at',
        'paid_at',
        'failed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'metadata' => 'array',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(IspWallet::class, 'isp_wallet_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markApproved(?int $userId = null): void
    {
        $this->forceFill([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ])->save();
    }

    public function markPaid(?string $receipt = null, array $response = []): void
    {
        $this->forceFill([
            'status' => 'paid',
            'mpesa_receipt_number' => $receipt ?: $this->mpesa_receipt_number,
            'response_payload' => $response ?: $this->response_payload,
            'paid_at' => now(),
            'processed_at' => $this->processed_at ?: now(),
        ])->save();
    }

    public function markFailed(string $reason, array $response = []): void
    {
        $this->forceFill([
            'status' => 'failed',
            'failure_reason' => $reason,
            'response_payload' => $response ?: $this->response_payload,
            'failed_at' => now(),
        ])->save();
    }
}