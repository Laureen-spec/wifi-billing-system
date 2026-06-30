<?php

namespace StudyRoomTechLab\IspWhatsapp\Models;

use App\Models\Customer;
use App\Models\InternetPackage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IspWhatsappPaymentRequest extends Model
{
    protected $table = 'isp_whatsapp_payment_requests';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(IspWhatsappConversation::class, 'conversation_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function internetPackage(): BelongsTo
    {
        return $this->belongsTo(InternetPackage::class, 'internet_package_id');
    }
}
