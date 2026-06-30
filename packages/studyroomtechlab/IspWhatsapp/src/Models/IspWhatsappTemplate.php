<?php

namespace StudyRoomTechLab\IspWhatsapp\Models;

use Illuminate\Database\Eloquent\Model;

class IspWhatsappTemplate extends Model
{
    protected $table = 'isp_whatsapp_templates';

    protected $guarded = [];

    protected $casts = [
        'variables' => 'array',
        'enabled' => 'boolean',
    ];

    public function isSendable(): bool
    {
        return $this->enabled && $this->status === 'approved';
    }
}
