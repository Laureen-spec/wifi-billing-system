<?php

namespace StudyRoomTechLab\IspSms\Models;

use Illuminate\Database\Eloquent\Model;

class IspSmsSetting extends Model
{
    protected $table = 'isp_sms_settings';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'allow_system_sms' => 'boolean',
        'allow_own_sms' => 'boolean',
        'free_sms_remaining' => 'integer',
        'sms_balance' => 'decimal:2',
        'estimated_cost_per_sms' => 'decimal:2',
        'low_balance_alert_enabled' => 'boolean',
        'low_balance_alert_threshold' => 'decimal:2',
        'low_balance_alerted_at' => 'datetime',
    ];
}
