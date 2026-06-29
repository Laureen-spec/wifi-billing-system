<?php

namespace StudyRoomTechLab\Loyalty\Models;

use App\Models\Customer;
use App\Models\Isp;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyPointTransaction extends Model
{
    public const TYPE_EARNED = 'earned';
    public const TYPE_REDEEMED = 'redeemed';
    public const TYPE_ADJUSTED = 'adjusted';
    public const TYPE_EXPIRED = 'expired';

    protected $table = 'loyalty_point_transactions';

    protected $fillable = [
        'isp_id',
        'customer_id',
        'loyalty_customer_point_id',
        'type',
        'points',
        'source_type',
        'source_id',
        'description',
        'created_by',
        'expires_at',
        'expired_at',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'expires_at' => 'datetime',
            'expired_at' => 'datetime',
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

    public function customerPoint(): BelongsTo
    {
        return $this->belongsTo(LoyaltyCustomerPoint::class, 'loyalty_customer_point_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function types(): array
    {
        return [
            self::TYPE_EARNED => 'Earned',
            self::TYPE_REDEEMED => 'Redeemed',
            self::TYPE_ADJUSTED => 'Adjusted',
            self::TYPE_EXPIRED => 'Expired',
        ];
    }
}
