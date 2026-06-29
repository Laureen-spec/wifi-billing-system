<?php

namespace StudyRoomTechLab\Loyalty\Models;

use App\Models\Customer;
use App\Models\Isp;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyVoucher extends Model
{
    public const STATUS_UNUSED = 'unused';
    public const STATUS_REDEEMED = 'redeemed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'loyalty_vouchers';

    protected $fillable = [
        'isp_id',
        'customer_id',
        'voucher_code',
        'points_used',
        'package_name',
        'duration_minutes',
        'status',
        'expires_at',
        'redeemed_at',
        'cancelled_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'points_used' => 'integer',
            'duration_minutes' => 'integer',
            'expires_at' => 'datetime',
            'redeemed_at' => 'datetime',
            'cancelled_at' => 'datetime',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_UNUSED => 'Unused',
            self::STATUS_REDEEMED => 'Redeemed',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }
}
