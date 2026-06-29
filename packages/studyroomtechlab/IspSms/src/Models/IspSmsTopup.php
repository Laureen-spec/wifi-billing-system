<?php

namespace StudyRoomTechLab\IspSms\Models;

use App\Models\Isp;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IspSmsTopup extends Model
{
    protected $table = 'isp_sms_topups';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'sms_units' => 'integer',
        'metadata' => 'array',
        'paid_at' => 'datetime',
    ];

    public function isp(): BelongsTo
    {
        return $this->belongsTo(Isp::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
}
