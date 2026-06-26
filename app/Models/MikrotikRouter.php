<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MikrotikRouter extends Model
{
    protected $fillable = [
        'isp_id',
        'name',
        'board_name',
        'identity',
        'routeros_version',
        'architecture',
        'uptime',
        'router_time',
        'cpu_load',
        'cpu_usage',
        'memory_free',
        'memory_total',
        'memory_usage',
        'host',
        'api_port',
        'username',
        'password',
        'provision_token',
        'heartbeat_token',
        'provision_status',
        'provisioned_at',
        'connection_type',
        'status',
        'last_connected_at',
        'last_seen_at',
        'last_heartbeat_payload',
        'winbox_endpoint',
        'winbox_port',
        'winbox_username',
        'winbox_password',
        'remote_winbox_status',
        'remote_winbox_error',
        'hotspot_status',
        'hotspot_files_status',
        'pppoe_status',
        'sync_status',
        'time_sync_status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'winbox_password' => 'encrypted',
            'last_connected_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'provisioned_at' => 'datetime',
            'last_heartbeat_payload' => 'array',
            'cpu_load' => 'integer',
            'memory_free' => 'integer',
            'memory_total' => 'integer',
            'memory_usage' => 'decimal:2',
        ];
    }

    public function isp(): BelongsTo
    {
        return $this->belongsTo(Isp::class);
    }

    public function provisioningTokens(): HasMany
    {
        return $this->hasMany(ProvisioningToken::class);
    }

    public function internetPackages(): BelongsToMany
    {
        return $this->belongsToMany(InternetPackage::class, 'internet_package_mikrotik_router')->withTimestamps();
    }
}
