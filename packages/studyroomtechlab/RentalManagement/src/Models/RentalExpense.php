<?php

namespace StudyRoomTechLab\RentalManagement\Models;

use Illuminate\Database\Eloquent\Model;

class RentalExpense extends Model
{
    protected $fillable = ['property_id', 'name', 'category', 'amount', 'expense_date', 'description', 'created_by'];

    protected $casts = [
        'expense_date' => 'date',
    ];
}
