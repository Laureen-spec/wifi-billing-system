<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    protected $fillable = [
        'isp_id',
        'internet_package_id',
        'name',
        'phone',
        'email',
        'location',
        'address',
        'connection_status',
        'billing_status',
        'monthly_amount',
        'installation_date',
        'next_due_date',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'monthly_amount' => 'decimal:2',
            'installation_date' => 'date',
            'next_due_date' => 'date',
        ];
    }

    public function isp(): BelongsTo
    {
        return $this->belongsTo(Isp::class);
    }

    public function internetPackage(): BelongsTo
    {
        return $this->belongsTo(InternetPackage::class);
    }
}
