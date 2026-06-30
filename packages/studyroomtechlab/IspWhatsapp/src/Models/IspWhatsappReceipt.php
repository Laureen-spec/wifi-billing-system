<?php

namespace StudyRoomTechLab\IspWhatsapp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IspWhatsappReceipt extends Model
{
    protected $table = 'isp_whatsapp_receipts';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'payload' => 'array',
        'sent_at' => 'datetime',
    ];

    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(IspWhatsappPaymentRequest::class, 'payment_request_id');
    }
}
