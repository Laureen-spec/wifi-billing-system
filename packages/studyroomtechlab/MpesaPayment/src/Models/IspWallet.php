<?php

namespace StudyRoomTechLab\MpesaPayment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IspWallet extends Model
{
    protected $table = 'isp_wallets';

    protected $fillable = [
        'isp_id',
        'available_balance',
        'pending_balance',
        'total_earned',
        'total_paid_out',
        'total_platform_fee',
        'currency',
        'payout_name',
        'payout_phone',
        'payout_shortcode',
        'payout_method',
        'auto_settlement_enabled',
        'minimum_settlement_amount',
        'settlement_schedule',
        'is_active',
    ];

    protected $casts = [
        'available_balance' => 'decimal:2',
        'pending_balance' => 'decimal:2',
        'total_earned' => 'decimal:2',
        'total_paid_out' => 'decimal:2',
        'total_platform_fee' => 'decimal:2',
        'minimum_settlement_amount' => 'decimal:2',
        'auto_settlement_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function ledger(): HasMany
    {
        return $this->hasMany(IspWalletLedger::class, 'isp_wallet_id');
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(IspSettlement::class, 'isp_wallet_id');
    }

    public function canSettle(): bool
    {
        return $this->is_active
            && $this->available_balance >= $this->minimum_settlement_amount;
    }

    public function credit(float $amount, string $description = null): void
    {
        $before = (float) $this->available_balance;
        $after = $before + $amount;

        $this->forceFill([
            'available_balance' => $after,
            'total_earned' => (float) $this->total_earned + $amount,
        ])->save();

        IspWalletLedger::create([
            'isp_id' => $this->isp_id,
            'isp_wallet_id' => $this->id,
            'type' => 'credit',
            'source' => 'mpesa_payment',
            'amount' => $amount,
            'balance_before' => $before,
            'balance_after' => $after,
            'currency' => $this->currency,
            'description' => $description,
        ]);
    }

    public function debit(float $amount, string $source = 'settlement', string $description = null): void
    {
        $before = (float) $this->available_balance;
        $after = max(0, $before - $amount);

        $this->forceFill([
            'available_balance' => $after,
            'total_paid_out' => (float) $this->total_paid_out + $amount,
        ])->save();

        IspWalletLedger::create([
            'isp_id' => $this->isp_id,
            'isp_wallet_id' => $this->id,
            'type' => 'debit',
            'source' => $source,
            'amount' => $amount,
            'balance_before' => $before,
            'balance_after' => $after,
            'currency' => $this->currency,
            'description' => $description,
        ]);
    }
}