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
        // Data-only registration migration. Keep module visibility rows intact on rollback.
    }

    private function syncAddonRow(): void
    {
        if (! Schema::hasTable('add_ons')) {
            return;
        }

        $record = [];
        $this->setAddonValue($record, 'module', 'Loyalty');
        $this->setAddonValue($record, 'name', 'Loyalty');
        $this->setAddonValue($record, 'alias', 'Loyalty');
        $this->setAddonValue($record, 'description', 'Customer loyalty points, reward rules, vouchers, and activity tracking for WiFi Billing.');
        $this->setAddonValue($record, 'package_name', 'loyalty');
        $this->setAddonValue($record, 'is_enable', 1);
        $this->setAddonValue($record, 'for_admin', 0);
        $this->setAddonValue($record, 'display', 1);
        $this->setAddonValue($record, 'status', 1);
        $this->setAddonValue($record, 'priority', 66);
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
                $query->orWhere('module', 'Loyalty');
            }

            if (Schema::hasColumn('add_ons', 'package_name')) {
                $query->orWhere('package_name', 'loyalty');
            }

            if (Schema::hasColumn('add_ons', 'name')) {
                $query->orWhere('name', 'Loyalty');
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
        $this->setMenuValue($record, 'menu_key', 'loyalty');
        $this->setMenuValue($record, 'label', 'Loyalty');
        $this->setMenuValue($record, 'menu_group', 'WiFi Billing');
        $this->setMenuValue($record, 'parent_key', null);
        $this->setMenuValue($record, 'route_name', 'loyalty.index');
        $this->setMenuValue($record, 'url', '/loyalty');
        $this->setMenuValue($record, 'aliases', json_encode([
            'Loyalty',
            'loyalty',
            'loyalty.index',
            'loyalty.customers',
            'loyalty.rules.index',
            'loyalty.vouchers.index',
            'loyalty.logs.index',
            'loyalty.settings',
            '/loyalty',
            '/loyalty/customers',
            '/loyalty/rules',
            '/loyalty/vouchers',
            '/loyalty/logs',
            '/loyalty/settings',
        ]));
        $this->setMenuValue($record, 'sort_order', 66);
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
            $query = DB::table('menu_visibility_settings')->where('menu_key', 'loyalty');

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
