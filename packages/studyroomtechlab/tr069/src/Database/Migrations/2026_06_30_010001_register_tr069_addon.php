<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->syncAddonRow();
        $this->syncMenuVisibilityRow();
    }

    public function down(): void
    {
        // Keep add-on/menu visibility records intact on rollback.
    }

    private function syncAddonRow(): void
    {
        if (! Schema::hasTable('add_ons')) {
            return;
        }

        $record = [];
        $this->setAddonValue($record, 'module', 'Tr069');
        $this->setAddonValue($record, 'name', 'TR069');
        $this->setAddonValue($record, 'alias', 'TR069');
        $this->setAddonValue($record, 'description', 'ACS-ready CPE provisioning, monitoring, configuration jobs, firmware records, and device logs.');
        $this->setAddonValue($record, 'package_name', 'tr069');
        $this->setAddonValue($record, 'is_enable', 1);
        $this->setAddonValue($record, 'for_admin', 0);
        $this->setAddonValue($record, 'display', 1);
        $this->setAddonValue($record, 'status', 1);
        $this->setAddonValue($record, 'priority', 67);
        $this->setAddonValue($record, 'version', 1.0);
        $this->setAddonValue($record, 'monthly_price', 0);
        $this->setAddonValue($record, 'yearly_price', 0);
        $this->setAddonValue($record, 'parent_module', json_encode(['WifiBilling']));
        $this->setAddonValue($record, 'updated_at', now());

        if ($record === []) {
            return;
        }

        $query = DB::table('add_ons')->where(function ($query): void {
            if (Schema::hasColumn('add_ons', 'module')) {
                $query->orWhere('module', 'Tr069');
            }

            if (Schema::hasColumn('add_ons', 'package_name')) {
                $query->orWhere('package_name', 'tr069');
            }

            if (Schema::hasColumn('add_ons', 'name')) {
                $query->orWhere('name', 'TR069');
            }
        });

        if ($query->exists()) {
            $query->update($record);
            return;
        }

        if (Schema::hasColumn('add_ons', 'created_at')) {
            $record['created_at'] = now();
        }

        DB::table('add_ons')->insert($record);
    }

    private function syncMenuVisibilityRow(): void
    {
        if (! Schema::hasTable('menu_visibility_settings')) {
            return;
        }

        $record = [];
        $this->setMenuValue($record, 'menu_key', 'tr069');
        $this->setMenuValue($record, 'label', 'TR069');
        $this->setMenuValue($record, 'menu_group', 'WiFi Billing');
        $this->setMenuValue($record, 'parent_key', null);
        $this->setMenuValue($record, 'route_name', 'tr069.index');
        $this->setMenuValue($record, 'url', '/tr069');
        $this->setMenuValue($record, 'aliases', json_encode([
            'TR069',
            'Tr069',
            'tr069',
            'tr069.index',
            'tr069.devices.index',
            'tr069.profiles.index',
            'tr069.jobs.index',
            'tr069.firmware.index',
            'tr069.logs.index',
            'tr069.settings',
            '/tr069',
            '/tr069/devices',
            '/tr069/profiles',
            '/tr069/config-jobs',
            '/tr069/firmware',
            '/tr069/logs',
            '/tr069/settings',
        ]));
        $this->setMenuValue($record, 'sort_order', 67);
        $this->setMenuValue($record, 'visible_to_superadmin', 1);
        $this->setMenuValue($record, 'visible_to_admin', 1);
        $this->setMenuValue($record, 'visible_to_isp_admin', 1);
        $this->setMenuValue($record, 'block_route_access', 0);
        $this->setMenuValue($record, 'is_system', 0);
        $this->setMenuValue($record, 'updated_at', now());

        if ($record === []) {
            return;
        }

        if (Schema::hasColumn('menu_visibility_settings', 'menu_key')) {
            $query = DB::table('menu_visibility_settings')->where('menu_key', 'tr069');

            if ($query->exists()) {
                $query->update($record);
                return;
            }
        }

        if (Schema::hasColumn('menu_visibility_settings', 'created_at')) {
            $record['created_at'] = now();
        }

        DB::table('menu_visibility_settings')->insert($record);
    }

    private function setAddonValue(array &$record, string $column, mixed $value): void
    {
        if (Schema::hasColumn('add_ons', $column)) {
            $record[$column] = $value;
        }
    }

    private function setMenuValue(array &$record, string $column, mixed $value): void
    {
        if (Schema::hasColumn('menu_visibility_settings', $column)) {
            $record[$column] = $value;
        }
    }
};
