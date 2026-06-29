<?php

namespace StudyRoomTechLab\RepairPos\Models;

use Illuminate\Database\Eloquent\Model;

class RepairProduct extends Model
{
    protected $table = 'repair_pos_products';

    protected $fillable = ['name', 'sku', 'category', 'brand', 'supplier', 'purchase_price', 'selling_price', 'stock', 'low_stock_alert', 'status', 'notes'];
}
