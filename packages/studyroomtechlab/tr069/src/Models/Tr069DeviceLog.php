<?php

namespace StudyRoomTechLab\Tr069\Models;

use App\Models\Isp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tr069DeviceLog extends Model
{
    protected $table = 'tr069_device_logs';

    protected $fillable = [
        'isp_id',
        'company_id',
        'cpe_device_id',
        'event_type',
        'level',
        'message',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function isp(): BelongsTo
    {
        return $this->belongsTo(Isp::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Tr069CpeDevice::class, 'cpe_device_id');
    }

    public static function eventTypes(): array
    {
        return [
            'inform' => 'Inform',
            'provision' => 'Provision',
            'reboot' => 'Reboot',
            'firmware' => 'Firmware',
            'diagnostics' => 'Diagnostics',
            'error' => 'Error',
            'status_change' => 'Status Change',
        ];
    }

    public static function levels(): array
    {
        return [
            'info' => 'Info',
            'warning' => 'Warning',
            'error' => 'Error',
            'success' => 'Success',
        ];
    }
}
