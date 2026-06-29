<?php

namespace StudyRoomTechLab\RepairPos\Models;

use Illuminate\Database\Eloquent\Model;

class RepairJob extends Model
{
    protected $table = 'repair_pos_jobs';

    protected $fillable = ['customer_id', 'customer_name', 'phone', 'device_type', 'device_model', 'serial_number', 'issue', 'diagnosis', 'estimated_cost', 'final_cost', 'amount_paid', 'priority', 'status', 'expected_pickup_date', 'warranty_expiry', 'assigned_to', 'created_by'];

    protected $casts = [
        'expected_pickup_date' => 'date',
        'warranty_expiry' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(RepairCustomer::class, 'customer_id');
    }
}
