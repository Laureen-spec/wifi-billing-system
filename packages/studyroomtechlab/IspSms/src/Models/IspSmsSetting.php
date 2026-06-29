<?php

namespace StudyRoomTechLab\IspSms\Models;

use Illuminate\Database\Eloquent\Model;

class IspSmsSetting extends Model
{
    protected $table = 'isp_sms_settings';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
