<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProvisioningLog extends Model
{
    protected $fillable = [
        'provisioning_token_id',
        'mikrotik_router_id',
        'customer_id',
        'token',
        'ip_address',
        'user_agent',
        'status',
        'message',
    ];

    public function provisioningToken(): BelongsTo
    {
        return $this->belongsTo(ProvisioningToken::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(MikrotikRouter::class, 'mikrotik_router_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
