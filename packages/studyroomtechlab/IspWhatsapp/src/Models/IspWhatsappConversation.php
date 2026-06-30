<?php

namespace StudyRoomTechLab\IspWhatsapp\Models;

use App\Models\Customer;
use App\Models\Isp;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IspWhatsappConversation extends Model
{
    protected $table = 'isp_whatsapp_conversations';

    protected $guarded = [];

    protected $casts = [
        'opted_out' => 'boolean',
        'blocked' => 'boolean',
        'tags' => 'array',
        'last_customer_message_at' => 'datetime',
        'handover_at' => 'datetime',
        'reply_window_expires_at' => 'datetime',
        'last_message_at' => 'datetime',
    ];

    public function isp(): BelongsTo
    {
        return $this->belongsTo(Isp::class, 'isp_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(IspWhatsappMessage::class, 'conversation_id');
    }

    public function replyWindowOpen(): bool
    {
        return $this->reply_window_expires_at !== null && $this->reply_window_expires_at->isFuture();
    }

    public function replyWindowStatus(): string
    {
        if ($this->replyWindowOpen()) {
            return 'open';
        }

        return 'expired';
    }

    public function requiresTemplate(): bool
    {
        return ! $this->replyWindowOpen();
    }
}
