<?php

namespace StudyRoomTechLab\Tr069\Models;

use App\Models\Isp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tr069FirmwareUpdate extends Model
{
    protected $table = 'tr069_firmware_updates';

    protected $fillable = [
        'isp_id',
        'company_id',
        'name',
        'version',
        'manufacturer',
        'model',
        'file_url',
        'checksum',
        'status',
        'notes',
    ];

    public function isp(): BelongsTo
    {
        return $this->belongsTo(Isp::class);
    }

    public static function statuses(): array
    {
        return [
            'draft' => 'Draft',
            'ready' => 'Ready',
            'queued' => 'Queued',
            'completed' => 'Completed',
            'failed' => 'Failed',
        ];
    }
}
