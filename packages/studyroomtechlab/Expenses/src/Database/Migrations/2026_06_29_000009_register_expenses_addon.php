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
        // Keep customer expense data and menu settings intact on rollback.
    }

    private function syncAddonRow(): void
    {
        if (! Schema::hasTable('add_ons')) {
            return;
        }

        $record = [];
        $this->setAddonValue($record, 'module', 'Expenses');
        $this->setAddonValue($record, 'name', 'Expenses');
        $this->setAddonValue($record, 'alias', 'Expenses');
        $this->setAddonValue($record, 'description', 'Standalone ISP expense ledger for outgoing money, receipts, categories, and net revenue reporting.');
        $this->setAddonValue($record, 'package_name', 'expenses');
        $this->setAddonValue($record, 'is_enable', 1);
        $this->setAddonValue($record, 'for_admin', 0);
        $this->setAddonValue($record, 'display', 1);
        $this->setAddonValue($record, 'status', 1);
        $this->setAddonValue($record, 'priority', 58);
        $this->setAddonValue($record, 'version', 1.0);
        $this->setAddonValue($record, 'monthly_price', 0);
        $this->setAddonValue($record, 'yearly_price', 0);
        $this->setAddonValue($record, 'parent_module', null);
        $this->setAddonValue($record, 'updated_at', now());

        if ($record === []) {
            return;
        }

        $query = DB::table('add_ons')->where(function ($query): void {
            if (Schema::hasColumn('add_ons', 'module')) {
                $query->orWhere('module', 'Expenses');
            }

            if (Schema::hasColumn('add_ons', 'package_name')) {
                $query->orWhere('package_name', 'expenses');
            }

            if (Schema::hasColumn('add_ons', 'name')) {
                $query->orWhere('name', 'Expenses');
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
        $this->setMenuValue($record, 'menu_key', 'expenses');
        $this->setMenuValue($record, 'label', 'Expenses');
        $this->setMenuValue($record, 'menu_group', 'Finance');
        $this->setMenuValue($record, 'parent_key', null);
        $this->setMenuValue($record, 'route_name', 'expenses.index');
        $this->setMenuValue($record, 'url', '/expenses');
        $this->setMenuValue($record, 'aliases', json_encode([
            'Expenses',
            'expenses',
            'expenses.index',
            '/expenses',
            'expense-ledger',
            'isp-expenses',
        ]));
        $this->setMenuValue($record, 'sort_order', 58);
        $this->setMenuValue($record, 'visible_to_superadmin', 0);
        $this->setMenuValue($record, 'visible_to_admin', 1);
        $this->setMenuValue($record, 'visible_to_isp_admin', 1);
        $this->setMenuValue($record, 'block_route_access', 0);
        $this->setMenuValue($record, 'is_system', 0);
        $this->setMenuValue($record, 'updated_at', now());

        if ($record === []) {
            return;
        }

        if (Schema::hasColumn('menu_visibility_settings', 'menu_key')) {
            $query = DB::table('menu_visibility_settings')->where('menu_key', 'expenses');

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
