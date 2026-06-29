<?php

namespace StudyRoomTechLab\RepairPos\Models;

use Illuminate\Database\Eloquent\Model;

class RepairSaleItem extends Model
{
    protected $table = 'repair_pos_sale_items';

    protected $fillable = ['sale_id', 'product_id', 'item_name', 'quantity', 'unit_price', 'subtotal'];

    public function product()
    {
        return $this->belongsTo(RepairProduct::class, 'product_id');
    }
}
