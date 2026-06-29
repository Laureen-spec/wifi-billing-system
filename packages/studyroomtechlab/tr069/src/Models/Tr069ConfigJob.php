<?php

namespace StudyRoomTechLab\Tr069\Models;

use App\Models\Isp;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tr069ConfigJob extends Model
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'tr069_config_jobs';

    protected $fillable = [
        'isp_id',
        'company_id',
        'cpe_device_id',
        'tr069_profile_id',
        'job_type',
        'payload',
        'status',
        'result_message',
        'queued_at',
        'started_at',
        'completed_at',
        'failed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'queued_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
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

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Tr069Profile::class, 'tr069_profile_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function jobTypes(): array
    {
        return [
            'provision' => 'Provision',
            'reboot' => 'Reboot',
            'wifi_update' => 'WiFi Update',
            'firmware_update' => 'Firmware Update',
            'parameter_push' => 'Parameter Push',
            'diagnostics' => 'Diagnostics',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_QUEUED => 'Queued',
            self::STATUS_RUNNING => 'Running',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }
}
