<?php

namespace StudyRoomTechLab\MpesaPayment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IspWalletLedger extends Model
{
    protected $table = 'isp_wallet_ledger';

    protected $fillable = [
        'isp_id',
        'isp_wallet_id',
        'type',
        'source',
        'mpesa_transaction_id',
        'isp_settlement_id',
        'customer_id',
        'amount',
        'balance_before',
        'balance_after',
        'currency',
        'reference',
        'description',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(IspWallet::class, 'isp_wallet_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(MpesaTransaction::class, 'mpesa_transaction_id');
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(IspSettlement::class, 'isp_settlement_id');
    }

    public function isCredit(): bool
    {
        return $this->type === 'credit';
    }

    public function isDebit(): bool
    {
        return in_array($this->type, ['debit', 'settlement', 'platform_fee'], true);
    }
}