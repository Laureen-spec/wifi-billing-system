<?php

namespace StudyRoomTechLab\Tr069\Models;

use App\Models\Isp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tr069Setting extends Model
{
    protected $table = 'tr069_settings';

    protected $fillable = [
        'isp_id',
        'company_id',
        'enabled',
        'acs_url',
        'api_token',
        'inform_interval',
        'connection_request_username',
        'connection_request_password',
        'default_profile_id',
        'allow_auto_register',
        'require_known_serial',
    ];

    protected $hidden = [
        'api_token',
        'connection_request_password',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'inform_interval' => 'integer',
            'allow_auto_register' => 'boolean',
            'require_known_serial' => 'boolean',
        ];
    }

    public function isp(): BelongsTo
    {
        return $this->belongsTo(Isp::class);
    }

    public function defaultProfile(): BelongsTo
    {
        return $this->belongsTo(Tr069Profile::class, 'default_profile_id');
    }

    public static function defaults(?int $ispId = null, ?int $companyId = null): array
    {
        return [
            'isp_id' => $ispId,
            'company_id' => $companyId,
            'enabled' => false,
            'acs_url' => null,
            'api_token' => null,
            'inform_interval' => 3600,
            'connection_request_username' => null,
            'connection_request_password' => null,
            'default_profile_id' => null,
            'allow_auto_register' => true,
            'require_known_serial' => false,
        ];
    }
}
