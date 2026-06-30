<?php

namespace StudyRoomTechLab\IspWhatsapp\Models;

use Illuminate\Database\Eloquent\Model;

class IspWhatsappTopup extends Model
{
    protected $table = 'isp_whatsapp_topups';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];
}
