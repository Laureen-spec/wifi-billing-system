<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class IspRoleSeeder extends Seeder
{
    public function run(): void
    {
        $creatorId = User::where('type', 'superadmin')->value('id')
            ?? User::where('type', 'super_admin')->value('id');

        $roles = [
            'super_admin' => 'Super Admin',
            'control_isp' => 'Control ISP',
            'isp_admin' => 'ISP Admin',
            'installer' => 'Installer',
            'customer' => 'Customer',
        ];

        foreach ($roles as $name => $label) {
            Role::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                [
                    'label' => $label,
                    'editable' => false,
                    'created_by' => $creatorId,
                ]
            );
        }
    }
}
