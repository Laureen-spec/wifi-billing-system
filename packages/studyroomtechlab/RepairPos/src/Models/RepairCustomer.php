<?php

namespace StudyRoomTechLab\RepairPos\Models;

use Illuminate\Database\Eloquent\Model;

class RepairCustomer extends Model
{
    protected $table = 'repair_pos_customers';

    protected $fillable = ['name', 'phone', 'email', 'location', 'notes', 'created_by'];

    public function jobs()
    {
        return $this->hasMany(RepairJob::class, 'customer_id');
    }
}
