<?php

namespace StudyRoomTechLab\IspWhatsapp\Models;

use Illuminate\Database\Eloquent\Model;

class IspWhatsappUsageLog extends Model
{
    protected $table = 'isp_whatsapp_usage_logs';

    protected $guarded = [];

    protected $casts = [
        'cost' => 'decimal:2',
        'sent_at' => 'datetime',
    ];
}
