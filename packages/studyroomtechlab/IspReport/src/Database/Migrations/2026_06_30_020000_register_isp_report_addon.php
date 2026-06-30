<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->syncAddonRow();
        $this->syncActiveModuleRows();
        $this->syncMenuVisibilityRows();
    }

    public function down(): void
    {
        if (Schema::hasTable('add_ons') && Schema::hasColumn('add_ons', 'module')) {
            DB::table('add_ons')->where('module', 'IspReport')->delete();
        }

        if (Schema::hasTable('menu_visibility_settings') && Schema::hasColumn('menu_visibility_settings', 'menu_key')) {
            DB::table('menu_visibility_settings')
                ->whereIn('menu_key', [
                    'isp-report',
                    'isp-report-overview',
                    'isp-report-staff-logs',
                    'isp-report-connection-logs',
                    'isp-report-payment-logs',
                ])
                ->delete();
        }
    }

    private function syncAddonRow(): void
    {
        if (! Schema::hasTable('add_ons')) {
            return;
        }

        $record = [];
        $this->setAddonValue($record, 'module', 'IspReport');
        $this->setAddonValue($record, 'name', 'ISP Report');
        $this->setAddonValue($record, 'alias', 'ISP Report');
        $this->setAddonValue($record, 'description', 'Reporting workspace for ISP overview, staff logs, connection logs, and payment logs.');
        $this->setAddonValue($record, 'package_name', 'isp-report');
        $this->setAddonValue($record, 'is_enable', 1);
        $this->setAddonValue($record, 'for_admin', 1);
        $this->setAddonValue($record, 'display', 1);
        $this->setAddonValue($record, 'status', 1);
        $this->setAddonValue($record, 'priority', 56);
        $this->setAddonValue($record, 'version', 1.0);
        $this->setAddonValue($record, 'monthly_price', 0);
        $this->setAddonValue($record, 'yearly_price', 0);
        $this->setAddonValue($record, 'parent_module', json_encode(['WifiBilling']));
        $this->setAddonValue($record, 'updated_at', now());

        if ($record === []) {
            return;
        }

        $query = DB::table('add_ons')->where(function ($query) {
            if (Schema::hasColumn('add_ons', 'module')) {
                $query->orWhere('module', 'IspReport');
            }

            if (Schema::hasColumn('add_ons', 'package_name')) {
                $query->orWhere('package_name', 'isp-report');
            }

            if (Schema::hasColumn('add_ons', 'name')) {
                $query->orWhere('name', 'ISP Report');
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


    private function syncActiveModuleRows(): void
    {
        if (! Schema::hasTable('user_active_modules') || ! Schema::hasTable('users')) {
            return;
        }

        $userIdColumn = Schema::hasColumn('user_active_modules', 'user_id') ? 'user_id' : null;
        $moduleColumn = Schema::hasColumn('user_active_modules', 'module') ? 'module' : null;

        if (! $userIdColumn || ! $moduleColumn) {
            return;
        }

        $companyUsers = DB::table('users')
            ->when(Schema::hasColumn('users', 'type'), fn ($query) => $query->where('type', 'company'))
            ->pluck('id');

        foreach ($companyUsers as $userId) {
            $exists = DB::table('user_active_modules')
                ->where($userIdColumn, $userId)
                ->where($moduleColumn, 'IspReport')
                ->exists();

            if ($exists) {
                continue;
            }

            $record = [
                $userIdColumn => $userId,
                $moduleColumn => 'IspReport',
            ];

            if (Schema::hasColumn('user_active_modules', 'created_at')) {
                $record['created_at'] = now();
            }

            if (Schema::hasColumn('user_active_modules', 'updated_at')) {
                $record['updated_at'] = now();
            }

            DB::table('user_active_modules')->insert($record);
        }
    }

    private function syncMenuVisibilityRows(): void
    {
        if (! Schema::hasTable('menu_visibility_settings')) {
            return;
        }

        $rows = [
            ['isp-report', 'ISP Report', null, 'isp-reports.index', '/wifi-billing/isp-reports', 56],
            ['isp-report-overview', 'Overview', 'isp-report', 'isp-reports.index', '/wifi-billing/isp-reports', 1],
            ['isp-report-staff-logs', 'Staff Logs', 'isp-report', 'isp-reports.staff-logs', '/wifi-billing/isp-reports/staff-logs', 2],
            ['isp-report-connection-logs', 'Connection Logs', 'isp-report', 'isp-reports.connection-logs', '/wifi-billing/isp-reports/connection-logs', 3],
            ['isp-report-payment-logs', 'Payment Logs', 'isp-report', 'isp-reports.payment-logs', '/wifi-billing/isp-reports/payment-logs', 4],
        ];

        foreach ($rows as [$key, $label, $parent, $route, $url, $order]) {
            $record = [];
            $this->setMenuValue($record, 'menu_key', $key);
            $this->setMenuValue($record, 'label', $label);
            $this->setMenuValue($record, 'menu_group', 'ISP Report');
            $this->setMenuValue($record, 'parent_key', $parent);
            $this->setMenuValue($record, 'route_name', $route);
            $this->setMenuValue($record, 'url', $url);
            $this->setMenuValue($record, 'aliases', json_encode([$key, $label, $route, $url]));
            $this->setMenuValue($record, 'sort_order', $order);
            $this->setMenuValue($record, 'visible_to_superadmin', 1);
            $this->setMenuValue($record, 'visible_to_admin', 1);
            $this->setMenuValue($record, 'visible_to_isp_admin', 1);
            $this->setMenuValue($record, 'block_route_access', 0);
            $this->setMenuValue($record, 'is_system', 0);
            $this->setMenuValue($record, 'updated_at', now());

            if ($record === []) {
                continue;
            }

            if (Schema::hasColumn('menu_visibility_settings', 'menu_key')) {
                $query = DB::table('menu_visibility_settings')->where('menu_key', $key);
                if ($query->exists()) {
                    $query->update($record);
                    continue;
                }
            }

            if (Schema::hasColumn('menu_visibility_settings', 'created_at')) {
                $record['created_at'] = now();
            }

            DB::table('menu_visibility_settings')->insert($record);
        }
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
