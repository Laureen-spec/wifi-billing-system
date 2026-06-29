<?php

namespace StudyRoomTechLab\Tr069\Models;

use App\Models\Isp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tr069Profile extends Model
{
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $table = 'tr069_profiles';

    protected $fillable = [
        'isp_id',
        'company_id',
        'name',
        'description',
        'wan_mode',
        'pppoe_username',
        'pppoe_password',
        'static_ip',
        'static_gateway',
        'static_dns',
        'vlan_id',
        'wifi_ssid',
        'wifi_password',
        'wifi_enabled',
        'tr069_parameters',
        'status',
    ];

    protected $hidden = [
        'pppoe_password',
        'wifi_password',
    ];

    protected function casts(): array
    {
        return [
            'vlan_id' => 'integer',
            'wifi_enabled' => 'boolean',
            'tr069_parameters' => 'array',
        ];
    }

    public function isp(): BelongsTo
    {
        return $this->belongsTo(Isp::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Tr069ConfigJob::class, 'tr069_profile_id');
    }

    public static function wanModes(): array
    {
        return [
            'dhcp' => 'DHCP',
            'pppoe' => 'PPPoE',
            'static' => 'Static IP',
            'bridge' => 'Bridge',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
        ];
    }
}
