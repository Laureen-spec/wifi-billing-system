<?php

namespace StudyRoomTechLab\IspWhatsapp\Models;

use App\Models\Customer;
use App\Models\Isp;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IspWhatsappMessage extends Model
{
    protected $table = 'isp_whatsapp_messages';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'cost' => 'decimal:2',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function isp(): BelongsTo
    {
        return $this->belongsTo(Isp::class, 'isp_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(IspWhatsappConversation::class, 'conversation_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(IspWhatsappTemplate::class, 'template_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
