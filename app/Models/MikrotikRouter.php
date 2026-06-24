<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MikrotikRouter extends Model
{
    protected $fillable = [
        'isp_id',
        'name',
        'host',
        'api_port',
        'username',
        'password',
        'provision_token',
        'provision_status',
        'provisioned_at',
        'connection_type',
        'status',
        'last_connected_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'last_connected_at' => 'datetime',
            'provisioned_at' => 'datetime',
        ];
    }

    public function isp(): BelongsTo
    {
        return $this->belongsTo(Isp::class);
    }
}
