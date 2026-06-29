<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminPaymentSetting extends Model
{
    protected $fillable = [
        'admin_id',
        'mode',
        'gateway',
        'method_type',
        'till_number',
        'paybill_number',
        'account_number',
        'phone_number',
        'fee_handling',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function isSystemPayment(): bool
    {
        return $this->mode === 'system_payment';
    }

    public function isOwnPayment(): bool
    {
        return $this->mode === 'own_payment';
    }
}
