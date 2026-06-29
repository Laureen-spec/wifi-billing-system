<?php

namespace StudyRoomTechLab\RentalManagement\Models;

use Illuminate\Database\Eloquent\Model;

class RentalInvoice extends Model
{
    protected $fillable = ['tenant_id', 'property_id', 'unit_id', 'invoice_number', 'invoice_type', 'amount', 'paid_amount', 'billing_month', 'due_date', 'status', 'notes'];

    protected $casts = [
        'billing_month' => 'date',
        'due_date' => 'date',
    ];

    public function tenant()
    {
        return $this->belongsTo(RentalTenant::class, 'tenant_id');
    }

    public function payments()
    {
        return $this->hasMany(RentalPayment::class, 'invoice_id');
    }
}
