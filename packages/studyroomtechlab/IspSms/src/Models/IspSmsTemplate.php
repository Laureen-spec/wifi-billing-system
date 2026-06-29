<?php

namespace StudyRoomTechLab\IspSms\Models;

use Illuminate\Database\Eloquent\Model;

class IspSmsTemplate extends Model
{
    protected $table = 'isp_sms_templates';

    protected $guarded = [];

    protected $casts = [
        'enabled' => 'boolean',
    ];
}
