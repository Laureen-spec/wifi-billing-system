<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuVisibilitySetting extends Model
{
    protected $fillable = [
        'menu_key',
        'label',
        'menu_group',
        'parent_key',
        'route_name',
        'url',
        'aliases',
        'sort_order',
        'visible_to_superadmin',
        'visible_to_admin',
        'visible_to_isp_admin',
        'block_route_access',
        'is_system',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'aliases' => 'array',
        'sort_order' => 'integer',
        'visible_to_superadmin' => 'boolean',
        'visible_to_admin' => 'boolean',
        'visible_to_isp_admin' => 'boolean',
        'block_route_access' => 'boolean',
        'is_system' => 'boolean',
    ];
}
