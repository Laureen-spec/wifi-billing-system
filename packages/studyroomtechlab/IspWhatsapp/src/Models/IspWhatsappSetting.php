<?php

namespace StudyRoomTechLab\IspWhatsapp\Models;

use Illuminate\Database\Eloquent\Model;

class IspWhatsappSetting extends Model
{
    protected $table = 'isp_whatsapp_settings';

    protected $guarded = [];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'is_active' => 'boolean',
        'allow_platform_api' => 'boolean',
        'allow_own_api' => 'boolean',
        'billing_enabled' => 'boolean',
        'reply_window_minutes' => 'integer',
        'whatsapp_balance' => 'decimal:2',
        'estimated_cost_per_message' => 'decimal:2',
        'low_balance_threshold' => 'decimal:2',
        'messages_sent' => 'integer',
        'messages_failed' => 'integer',
        'last_billed_at' => 'datetime',
        'last_tested_at' => 'datetime',
    ];

    public function usesPlatformApi(): bool
    {
        return $this->provider_mode === 'platform';
    }

    public function usesOwnApi(): bool
    {
        return $this->provider_mode === 'own_api';
    }

    public function messageCost(): float
    {
        return max(0, (float) ($this->estimated_cost_per_message ?? config('isp-whatsapp.default_message_cost', 1)));
    }
}
