<?php

namespace StudyRoomTechLab\Tr069\Models;

use App\Models\Customer;
use App\Models\Isp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tr069CpeDevice extends Model
{
    public const STATUS_ONLINE = 'online';
    public const STATUS_OFFLINE = 'offline';
    public const STATUS_PENDING = 'pending';
    public const STATUS_ERROR = 'error';

    protected $table = 'tr069_cpe_devices';

    protected $fillable = [
        'isp_id',
        'company_id',
        'customer_id',
        'serial_number',
        'oui',
        'product_class',
        'manufacturer',
        'model',
        'firmware_version',
        'hardware_version',
        'ip_address',
        'mac_address',
        'connection_request_url',
        'connection_username',
        'connection_password',
        'last_inform_at',
        'last_seen_ip',
        'status',
        'notes',
        'metadata',
    ];

    protected $hidden = [
        'connection_password',
    ];

    protected function casts(): array
    {
        return [
            'last_inform_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function isp(): BelongsTo
    {
        return $this->belongsTo(Isp::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Tr069ConfigJob::class, 'cpe_device_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(Tr069DeviceLog::class, 'cpe_device_id');
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_ONLINE => 'Online',
            self::STATUS_OFFLINE => 'Offline',
            self::STATUS_PENDING => 'Pending',
            self::STATUS_ERROR => 'Error',
        ];
    }
}
