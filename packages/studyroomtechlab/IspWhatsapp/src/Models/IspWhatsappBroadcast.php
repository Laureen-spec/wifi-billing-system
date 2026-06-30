<?php

namespace StudyRoomTechLab\IspWhatsapp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IspWhatsappBroadcast extends Model
{
    protected $table = 'isp_whatsapp_broadcasts';

    protected $guarded = [];

    protected $casts = [
        'filters' => 'array',
        'requires_confirmation' => 'boolean',
        'recipient_count' => 'integer',
        'confirmed_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(IspWhatsappTemplate::class, 'template_id');
    }
}
