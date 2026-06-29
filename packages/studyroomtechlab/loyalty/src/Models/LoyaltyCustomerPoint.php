<?php

namespace StudyRoomTechLab\Loyalty\Models;

use App\Models\Customer;
use App\Models\Isp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyCustomerPoint extends Model
{
    protected $table = 'loyalty_customer_points';

    protected $fillable = [
        'isp_id',
        'customer_id',
        'current_points',
        'lifetime_points',
        'redeemed_points',
        'last_awarded_at',
    ];

    protected function casts(): array
    {
        return [
            'current_points' => 'integer',
            'lifetime_points' => 'integer',
            'redeemed_points' => 'integer',
            'last_awarded_at' => 'datetime',
        ];
    }

    public function isp(): BelongsTo
    {
        return $this->belongsTo(Isp::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyPointTransaction::class, 'loyalty_customer_point_id');
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(LoyaltyVoucher::class, 'customer_id', 'customer_id');
    }
}
