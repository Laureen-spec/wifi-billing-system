<?php

namespace StudyRoomTechLab\Loyalty\Models;

use App\Models\Isp;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoyaltyRewardRule extends Model
{
    use SoftDeletes;

    public const TRIGGER_SUCCESSFUL_PAYMENT = 'successful_payment';
    public const TRIGGER_AMOUNT_SPENT = 'amount_spent';
    public const TRIGGER_RENEWAL_COUNT = 'renewal_count';
    public const TRIGGER_ON_TIME_PAYMENT = 'on_time_payment';
    public const TRIGGER_MANUAL_BONUS = 'manual_bonus';

    protected $table = 'loyalty_reward_rules';

    protected $fillable = [
        'isp_id',
        'name',
        'trigger_type',
        'points_value',
        'amount_step',
        'renewal_count',
        'auto_voucher',
        'voucher_threshold',
        'voucher_package_name',
        'voucher_duration_minutes',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'points_value' => 'integer',
            'amount_step' => 'decimal:2',
            'renewal_count' => 'integer',
            'auto_voucher' => 'boolean',
            'voucher_threshold' => 'integer',
            'voucher_duration_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function isp(): BelongsTo
    {
        return $this->belongsTo(Isp::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function triggers(): array
    {
        return [
            self::TRIGGER_SUCCESSFUL_PAYMENT => 'Successful Payment',
            self::TRIGGER_AMOUNT_SPENT => 'Amount Spent',
            self::TRIGGER_RENEWAL_COUNT => 'Renewal Count',
            self::TRIGGER_ON_TIME_PAYMENT => 'On-time Payment',
            self::TRIGGER_MANUAL_BONUS => 'Manual Bonus',
        ];
    }
}
