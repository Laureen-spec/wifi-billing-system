<?php

namespace StudyRoomTechLab\RentalManagement\Models;

use Illuminate\Database\Eloquent\Model;

class RentalProperty extends Model
{
    protected $fillable = ['owner_id', 'name', 'type', 'location', 'manager_name', 'manager_phone', 'description', 'status', 'created_by'];

    public function units()
    {
        return $this->hasMany(RentalUnit::class, 'property_id');
    }

    public function tenants()
    {
        return $this->hasMany(RentalTenant::class, 'property_id');
    }
}
