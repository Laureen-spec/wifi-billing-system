<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InternetPackage extends Model
{
    protected $fillable = [
        'isp_id',
        'name',
        'package_type',
        'access_type',
        'price',
        'download_speed_mbps',
        'upload_speed_mbps',
        'billing_cycle',
        'validity_days',
        'status',
        'enable_burst',
        'burst_download',
        'burst_upload',
        'burst_threshold',
        'burst_time',
        'priority',
        'limit_at',
        'enable_schedule',
        'schedule_start',
        'schedule_end',
        'schedule_days',
        'schedule_recurring',
        'available_on_all_mikrotik',
        'hidden_from_client',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'enable_burst' => 'boolean',
            'enable_schedule' => 'boolean',
            'schedule_days' => 'array',
            'schedule_recurring' => 'boolean',
            'available_on_all_mikrotik' => 'boolean',
            'hidden_from_client' => 'boolean',
        ];
    }

    public function isp(): BelongsTo
    {
        return $this->belongsTo(Isp::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function provisioningTokens(): HasMany
    {
        return $this->hasMany(ProvisioningToken::class);
    }

    public function mikrotikRouters(): BelongsToMany
    {
        return $this->belongsToMany(MikrotikRouter::class, 'internet_package_mikrotik_router')->withTimestamps();
    }
}
