<?php

namespace StudyRoomTechLab\IspSms\Models;

use App\Models\Customer;
use App\Models\Isp;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IspSmsMessage extends Model
{
    protected $table = 'isp_sms_messages';

    protected $guarded = [];

    protected $casts = [
        'provider_response' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function isp(): BelongsTo
    {
        return $this->belongsTo(Isp::class, 'isp_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function recipientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }
}
