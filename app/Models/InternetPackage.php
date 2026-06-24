<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InternetPackage extends Model
{
    protected $fillable = [
        'isp_id',
        'name',
        'price',
        'download_speed_mbps',
        'upload_speed_mbps',
        'billing_cycle',
        'validity_days',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    public function isp(): BelongsTo
    {
        return $this->belongsTo(Isp::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
