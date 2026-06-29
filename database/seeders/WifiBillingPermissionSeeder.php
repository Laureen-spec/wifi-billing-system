<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class WifiBillingPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'manage-wifi-dashboard', 'module' => 'wifi-dashboard', 'label' => 'Manage WiFi Billing Dashboard'],
            ['name' => 'view-wifi-dashboard', 'module' => 'wifi-dashboard', 'label' => 'View WiFi Billing Dashboard'],

            ['name' => 'manage-internet-packages', 'module' => 'internet-packages', 'label' => 'Manage Internet Packages'],
            ['name' => 'view-internet-packages', 'module' => 'internet-packages', 'label' => 'View Internet Packages'],
            ['name' => 'create-internet-packages', 'module' => 'internet-packages', 'label' => 'Create Internet Packages'],
            ['name' => 'edit-internet-packages', 'module' => 'internet-packages', 'label' => 'Edit Internet Packages'],
            ['name' => 'delete-internet-packages', 'module' => 'internet-packages', 'label' => 'Delete Internet Packages'],

            ['name' => 'manage-isp-customers', 'module' => 'isp-customers', 'label' => 'Manage ISP Customers'],
            ['name' => 'view-isp-customers', 'module' => 'isp-customers', 'label' => 'View ISP Customers'],
            ['name' => 'create-isp-customers', 'module' => 'isp-customers', 'label' => 'Create ISP Customers'],
            ['name' => 'edit-isp-customers', 'module' => 'isp-customers', 'label' => 'Edit ISP Customers'],
            ['name' => 'delete-isp-customers', 'module' => 'isp-customers', 'label' => 'Delete ISP Customers'],

            ['name' => 'manage-mikrotik-routers', 'module' => 'mikrotik-routers', 'label' => 'Manage MikroTik Routers'],
            ['name' => 'view-mikrotik-routers', 'module' => 'mikrotik-routers', 'label' => 'View MikroTik Routers'],
            ['name' => 'create-mikrotik-routers', 'module' => 'mikrotik-routers', 'label' => 'Create MikroTik Routers'],
            ['name' => 'edit-mikrotik-routers', 'module' => 'mikrotik-routers', 'label' => 'Edit MikroTik Routers'],
            ['name' => 'delete-mikrotik-routers', 'module' => 'mikrotik-routers', 'label' => 'Delete MikroTik Routers'],
            ['name' => 'reprovision-mikrotik-routers', 'module' => 'mikrotik-routers', 'label' => 'Reprovision MikroTik Routers'],
            ['name' => 'sync-hotspot-files', 'module' => 'mikrotik-routers', 'label' => 'Sync Hotspot Files'],
            ['name' => 'sync-router-time', 'module' => 'mikrotik-routers', 'label' => 'Sync Router Time'],

            ['name' => 'manage-provisioning', 'module' => 'provisioning', 'label' => 'Manage Provisioning'],
            ['name' => 'view-provisioning-logs', 'module' => 'provisioning', 'label' => 'View Provisioning Logs'],
            ['name' => 'create-provisioning-token', 'module' => 'provisioning', 'label' => 'Create Provisioning Token'],
            ['name' => 'deactivate-provisioning-token', 'module' => 'provisioning', 'label' => 'Deactivate Provisioning Token'],

            ['name' => 'manage-vouchers', 'module' => 'vouchers', 'label' => 'Manage Vouchers'],
            ['name' => 'view-vouchers', 'module' => 'vouchers', 'label' => 'View Vouchers'],
            ['name' => 'create-vouchers', 'module' => 'vouchers', 'label' => 'Create Vouchers'],
            ['name' => 'edit-vouchers', 'module' => 'vouchers', 'label' => 'Edit Vouchers'],
            ['name' => 'delete-vouchers', 'module' => 'vouchers', 'label' => 'Delete Vouchers'],
        ];

        $companyRole = Role::firstOrCreate(
            ['name' => 'company', 'guard_name' => 'web'],
            ['label' => 'Company', 'editable' => false]
        );

        $permissionModels = [];

        foreach ($permissions as $permission) {
            $permissionModels[] = Permission::updateOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                [
                    'module' => $permission['module'],
                    'label' => $permission['label'],
                    // Use the exact installed module name from module.json.
                    // This prevents WiFiBilling/WifiBilling case mismatch from hiding permissions.
                    'add_on' => 'WifiBilling',
                ]
            );
        }

        $companyRole->givePermissionTo($permissionModels);

        User::where('type', 'company')->chunkById(100, function ($users) use ($companyRole): void {
            foreach ($users as $user) {
                if (! $user->hasRole($companyRole->name)) {
                    $user->assignRole($companyRole);
                }
            }
        });

        if (app()->bound(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        try {
            Artisan::call('permission:cache-reset');
        } catch (\Throwable $e) {
            // Some installs do not expose Spatie's cache reset command. optimize:clear is enough then.
        }
    }
}
