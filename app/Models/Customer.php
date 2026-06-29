<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $table = 'isp_customers';

    protected $fillable = [
        'isp_id',
        'internet_package_id',
        'mikrotik_router_id',
        'access_type',
        'username',
        'password',
        'mac_address',
        'ip_address',
        'shared_users',
        'data_used_bytes',
        'last_online_at',
        'name',
        'phone',
        'email',
        'location',
        'address',
        'connection_status',
        'billing_status',
        'provisioning_status',
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
            'last_online_at' => 'datetime',
            'data_used_bytes' => 'integer',
            'shared_users' => 'integer',
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

    public function mikrotikRouter(): BelongsTo
    {
        return $this->belongsTo(MikrotikRouter::class, 'mikrotik_router_id');
    }

    public function provisioningTokens(): HasMany
    {
        return $this->hasMany(ProvisioningToken::class);
    }

    public function getDisplayUsernameAttribute(): string
    {
        return (string) ($this->username ?: $this->phone ?: 'customer' . $this->id);
    }

    public function getDataUsedHumanAttribute(): string
    {
        $bytes = (int) ($this->data_used_bytes ?? 0);
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
