<?php

namespace StudyRoomTechLab\RentalManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentalTenant extends Model
{
    use SoftDeletes;

    protected $fillable = ['property_id', 'unit_id', 'name', 'phone', 'email', 'guardian_phone', 'move_in_date', 'move_out_date', 'rent_balance', 'water_balance', 'internet_balance', 'status', 'notes', 'created_by'];

    protected $casts = [
        'move_in_date' => 'date',
        'move_out_date' => 'date',
    ];

    public function property()
    {
        return $this->belongsTo(RentalProperty::class, 'property_id');
    }

    public function unit()
    {
        return $this->belongsTo(RentalUnit::class, 'unit_id');
    }

    public function invoices()
    {
        return $this->hasMany(RentalInvoice::class, 'tenant_id');
    }
}
