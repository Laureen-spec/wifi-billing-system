<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $addons = [
        [
            'module' => 'IspSms',
            'name' => 'ISP SMS',
            'alias' => 'ISP SMS',
            'package_name' => 'isp-sms',
            'description' => 'SMS communication workspace for ISP customer alerts, templates, gateway settings, and delivery logs.',
            'priority' => 55,
            'menu_key' => 'isp-sms',
            'route_name' => 'isp.sms.index',
            'url' => '/isp/sms',
            'aliases' => ['IspSms', 'ISP SMS', 'isp-sms', 'sms', 'isp.sms.index', 'isp.sms.settings', 'isp.sms.templates.index', '/isp/sms'],
        ],
        [
            'module' => 'IspReport',
            'name' => 'ISP Report',
            'alias' => 'ISP Report',
            'package_name' => 'isp-report',
            'description' => 'Professional reporting workspace for billing overview, staff logs, connection logs, and payment logs.',
            'priority' => 56,
            'menu_key' => 'isp-report',
            'route_name' => 'isp-reports.index',
            'url' => '/wifi-billing/isp-reports',
            'aliases' => ['IspReport', 'ISP Report', 'isp-report', 'reports', 'isp-reports.index', '/wifi-billing/isp-reports'],
        ],
        [
            'module' => 'Tr069',
            'name' => 'TR069',
            'alias' => 'TR069',
            'package_name' => 'tr069',
            'description' => 'ACS-ready CPE provisioning, monitoring, configuration jobs, firmware records, and device logs.',
            'priority' => 67,
            'menu_key' => 'tr069',
            'route_name' => 'tr069.index',
            'url' => '/tr069',
            'aliases' => ['Tr069', 'TR069', 'tr069', 'tr-069', 'tr069.index', 'tr069.devices.index', 'tr069.settings', '/tr069'],
        ],
        [
            'module' => 'Loyalty',
            'name' => 'Loyalty',
            'alias' => 'Loyalty',
            'package_name' => 'loyalty',
            'description' => 'Customer loyalty points, reward rules, vouchers, and activity tracking for WiFi Billing.',
            'priority' => 66,
            'menu_key' => 'loyalty',
            'route_name' => 'loyalty.index',
            'url' => '/loyalty',
            'aliases' => ['Loyalty', 'loyalty', 'loyalty.index', 'loyalty.customers', 'loyalty.settings', '/loyalty'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->addons as $addon) {
            $this->syncAddon($addon);
            $this->syncMenuVisibility($addon);
            $this->activateForCompanyAccounts($addon['module']);
        }
    }

    public function down(): void
    {
        // Keep synced add-on and active-module records. This is a repair migration.
    }

    private function syncAddon(array $addon): void
    {
        if (! Schema::hasTable('add_ons')) {
            return;
        }

        $record = [];
        $this->setValue('add_ons', $record, 'module', $addon['module']);
        $this->setValue('add_ons', $record, 'name', $addon['name']);
        $this->setValue('add_ons', $record, 'alias', $addon['alias']);
        $this->setValue('add_ons', $record, 'description', $addon['description']);
        $this->setValue('add_ons', $record, 'package_name', $addon['package_name']);
        $this->setValue('add_ons', $record, 'is_enable', 1);
        $this->setValue('add_ons', $record, 'display', 1);
        $this->setValue('add_ons', $record, 'status', 1);
        $this->setValue('add_ons', $record, 'priority', $addon['priority']);
        $this->setValue('add_ons', $record, 'version', 1.0);
        $this->setValue('add_ons', $record, 'monthly_price', 0);
        $this->setValue('add_ons', $record, 'yearly_price', 0);
        $this->setValue('add_ons', $record, 'parent_module', json_encode(['WifiBilling']));
        $this->setValue('add_ons', $record, 'updated_at', now());

        if ($record === []) {
            return;
        }

        $query = DB::table('add_ons')->where(function ($query) use ($addon): void {
            if (Schema::hasColumn('add_ons', 'module')) {
                $query->orWhere('module', $addon['module']);
            }
            if (Schema::hasColumn('add_ons', 'package_name')) {
                $query->orWhere('package_name', $addon['package_name']);
            }
            if (Schema::hasColumn('add_ons', 'name')) {
                $query->orWhere('name', $addon['name']);
            }
        });

        if ($query->exists()) {
            $query->update($record);
            return;
        }

        $this->setValue('add_ons', $record, 'created_at', now());
        DB::table('add_ons')->insert($record);
    }

    private function syncMenuVisibility(array $addon): void
    {
        if (! Schema::hasTable('menu_visibility_settings')) {
            return;
        }

        $record = [];
        $this->setValue('menu_visibility_settings', $record, 'menu_key', $addon['menu_key']);
        $this->setValue('menu_visibility_settings', $record, 'label', $addon['alias']);
        $this->setValue('menu_visibility_settings', $record, 'menu_group', 'WiFi Billing');
        $this->setValue('menu_visibility_settings', $record, 'parent_key', null);
        $this->setValue('menu_visibility_settings', $record, 'route_name', $addon['route_name']);
        $this->setValue('menu_visibility_settings', $record, 'url', $addon['url']);
        $this->setValue('menu_visibility_settings', $record, 'aliases', json_encode($addon['aliases']));
        $this->setValue('menu_visibility_settings', $record, 'sort_order', $addon['priority']);
        $this->setValue('menu_visibility_settings', $record, 'visible_to_superadmin', 1);
        $this->setValue('menu_visibility_settings', $record, 'visible_to_admin', 1);
        $this->setValue('menu_visibility_settings', $record, 'visible_to_isp_admin', 1);
        $this->setValue('menu_visibility_settings', $record, 'block_route_access', 0);
        $this->setValue('menu_visibility_settings', $record, 'is_system', 0);
        $this->setValue('menu_visibility_settings', $record, 'updated_at', now());

        if ($record === []) {
            return;
        }

        if (Schema::hasColumn('menu_visibility_settings', 'menu_key')) {
            $query = DB::table('menu_visibility_settings')->where('menu_key', $addon['menu_key']);
            if ($query->exists()) {
                $query->update($record);
                return;
            }
        }

        $this->setValue('menu_visibility_settings', $record, 'created_at', now());
        DB::table('menu_visibility_settings')->insert($record);
    }

    private function activateForCompanyAccounts(string $module): void
    {
        if (! Schema::hasTable('user_active_modules') || ! Schema::hasTable('users')) {
            return;
        }

        $userQuery = DB::table('users');
        if (Schema::hasColumn('users', 'type')) {
            $userQuery->whereIn('type', ['company', 'isp_admin']);
        }

        $userQuery->select('id')->orderBy('id')->chunkById(100, function ($users) use ($module): void {
            foreach ($users as $user) {
                $record = [
                    'user_id' => $user->id,
                    'module' => $module,
                ];

                $update = [];
                if (Schema::hasColumn('user_active_modules', 'updated_at')) {
                    $update['updated_at'] = now();
                }

                $insert = array_merge($record, $update);
                if (Schema::hasColumn('user_active_modules', 'created_at')) {
                    $insert['created_at'] = now();
                }

                DB::table('user_active_modules')->updateOrInsert($record, $insert);
            }
        });
    }

    private function setValue(string $table, array &$record, string $column, mixed $value): void
    {
        if (Schema::hasColumn($table, $column)) {
            $record[$column] = $value;
        }
    }
};
