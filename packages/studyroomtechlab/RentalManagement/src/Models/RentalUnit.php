<?php

namespace StudyRoomTechLab\RentalManagement\Models;

use Illuminate\Database\Eloquent\Model;

class RentalUnit extends Model
{
    protected $fillable = ['property_id', 'unit_number', 'unit_type', 'rent_amount', 'deposit_amount', 'capacity', 'status', 'notes'];

    public function property()
    {
        return $this->belongsTo(RentalProperty::class, 'property_id');
    }

    public function tenant()
    {
        return $this->hasOne(RentalTenant::class, 'unit_id')->where('status', 'active');
    }
}
