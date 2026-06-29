<?php

namespace StudyRoomTechLab\Loyalty\Models;

use App\Models\Isp;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltySetting extends Model
{
    protected $table = 'loyalty_settings';

    protected $fillable = [
        'isp_id',
        'enabled',
        'default_points_per_payment',
        'points_per_amount',
        'amount_step',
        'voucher_threshold',
        'voucher_package_name',
        'voucher_duration_minutes',
        'points_expiry_days',
        'auto_generate_voucher',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'default_points_per_payment' => 'integer',
            'points_per_amount' => 'integer',
            'amount_step' => 'decimal:2',
            'voucher_threshold' => 'integer',
            'voucher_duration_minutes' => 'integer',
            'points_expiry_days' => 'integer',
            'auto_generate_voucher' => 'boolean',
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

    public static function defaults(?int $ispId = null): array
    {
        return [
            'isp_id' => $ispId,
            'enabled' => true,
            'default_points_per_payment' => 10,
            'points_per_amount' => 1,
            'amount_step' => 100,
            'voucher_threshold' => 100,
            'voucher_package_name' => null,
            'voucher_duration_minutes' => 60,
            'points_expiry_days' => null,
            'auto_generate_voucher' => true,
        ];
    }
}
