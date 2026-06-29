<?php

namespace StudyRoomTechLab\RentalManagement\Models;

use Illuminate\Database\Eloquent\Model;

class RentalPayment extends Model
{
    protected $fillable = ['invoice_id', 'tenant_id', 'amount', 'payment_method', 'reference', 'paid_at', 'status', 'notes', 'created_by'];

    protected $casts = [
        'paid_at' => 'datetime',
    ];
}
