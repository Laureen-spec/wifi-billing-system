<?php

namespace StudyRoomTechLab\RepairPos\Models;

use Illuminate\Database\Eloquent\Model;

class RepairSale extends Model
{
    protected $table = 'repair_pos_sales';

    protected $fillable = ['sale_number', 'customer_id', 'repair_job_id', 'subtotal', 'discount', 'tax', 'total', 'amount_paid', 'payment_method', 'payment_reference', 'status', 'sold_at', 'created_by'];

    protected $casts = [
        'sold_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(RepairCustomer::class, 'customer_id');
    }

    public function items()
    {
        return $this->hasMany(RepairSaleItem::class, 'sale_id');
    }
}
