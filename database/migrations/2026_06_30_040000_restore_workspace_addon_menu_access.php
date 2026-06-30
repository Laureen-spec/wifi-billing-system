<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private array $workspaceAddons = [
        [
            'module' => 'IspSms',
            'name' => 'ISP SMS',
            'package_name' => 'isp-sms',
            'priority' => 55,
            'for_admin' => 0,
            'menu_key' => 'isp-sms',
            'route_name' => 'isp.sms.index',
            'aliases' => ['IspSms', 'isp-sms', 'sms', 'messages', 'isp.sms.index', 'isp.sms.settings', 'isp.sms.templates.index'],
        ],
        [
            'module' => 'IspReport',
            'name' => 'ISP Report',
            'package_name' => 'isp-report',
            'priority' => 56,
            'for_admin' => 1,
            'menu_key' => 'isp-report',
            'route_name' => 'isp-reports.index',
            'aliases' => ['IspReport', 'isp-report', 'isp-reports', 'reports', 'staff-logs', 'connection-logs', 'payment-logs'],
        ],
        [
            'module' => 'Tr069',
            'name' => 'TR069',
            'package_name' => 'tr069',
            'priority' => 67,
            'for_admin' => 0,
            'menu_key' => 'tr069',
            'route_name' => 'tr069.index',
            'aliases' => ['Tr069', 'TR069', 'tr069', 'tr-069', 'cwmp', 'cpe', 'acs', 'tr069.devices.index'],
        ],
        [
            'module' => 'Loyalty',
            'name' => 'Loyalty',
            'package_name' => 'loyalty',
            'priority' => 66,
            'for_admin' => 0,
            'menu_key' => 'loyalty',
            'route_name' => 'loyalty.index',
            'aliases' => ['Loyalty', 'loyalty', 'rewards', 'reward', 'vouchers', 'points'],
        ],
    ];

    public function up(): void
    {
        $this->syncAddOns();
        $this->syncUserActiveModules();
        $this->syncMenuVisibility();
    }

    public function down(): void
    {
        // Keep installed add-ons and existing menu visibility choices intact.
    }

    private function syncAddOns(): void
    {
        if (! Schema::hasTable('add_ons')) {
            return;
        }

        $columns = Schema::getColumnListing('add_ons');

        foreach ($this->workspaceAddons as $addon) {
            $existing = DB::table('add_ons')
                ->where(function ($query) use ($addon) {
                    $query->where('module', $addon['module'])
                        ->orWhere('package_name', $addon['package_name']);
                })
                ->first();

            $payload = $this->onlyExistingColumns($columns, [
                'module' => $addon['module'],
                'name' => $addon['name'],
                'monthly_price' => 0,
                'yearly_price' => 0,
                'is_enable' => 1,
                'for_admin' => $addon['for_admin'],
                'package_name' => $addon['package_name'],
                'priority' => $addon['priority'],
                'updated_at' => now(),
            ]);

            if ($existing) {
                DB::table('add_ons')->where('id', $existing->id)->update($payload);
                continue;
            }

            $payload = array_merge($payload, $this->onlyExistingColumns($columns, [
                'created_at' => now(),
            ]));

            DB::table('add_ons')->insert($payload);
        }
    }

    private function syncUserActiveModules(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('user_active_modules')) {
            return;
        }

        $userColumns = Schema::getColumnListing('users');
        $activeColumns = Schema::getColumnListing('user_active_modules');

        $query = DB::table('users')->select('id');

        if (in_array('type', $userColumns, true)) {
            $query->where(function ($inner) {
                $inner->whereNull('type')
                    ->orWhereNotIn('type', ['superadmin', 'super_admin', 'client', 'customer', 'vendor', 'tenant']);
            });
        }

        $userIds = $query->pluck('id');
        if ($userIds->isEmpty()) {
            return;
        }

        foreach ($userIds as $userId) {
            foreach ($this->workspaceAddons as $addon) {
                $exists = DB::table('user_active_modules')
                    ->where('user_id', $userId)
                    ->where('module', $addon['module'])
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('user_active_modules')->insert($this->onlyExistingColumns($activeColumns, [
                    'user_id' => $userId,
                    'module' => $addon['module'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    private function syncMenuVisibility(): void
    {
        if (! Schema::hasTable('menu_visibility_settings')) {
            return;
        }

        $columns = Schema::getColumnListing('menu_visibility_settings');

        foreach ($this->workspaceAddons as $addon) {
            $existing = DB::table('menu_visibility_settings')
                ->where('menu_key', $addon['menu_key'])
                ->first();

            $payload = $this->onlyExistingColumns($columns, [
                'menu_key' => $addon['menu_key'],
                'label' => $addon['name'],
                'menu_group' => 'StudyRoom Add-ons',
                'parent_key' => null,
                'route_name' => $addon['route_name'],
                'url' => null,
                'aliases' => json_encode($this->expandedAliases(array_merge([$addon['menu_key'], $addon['name'], $addon['route_name']], $addon['aliases']))),
                'sort_order' => $addon['priority'],
                'visible_to_superadmin' => 1,
                'visible_to_admin' => 1,
                'visible_to_isp_admin' => 1,
                'block_route_access' => 0,
                'is_system' => 1,
                'updated_at' => now(),
            ]);

            if ($existing) {
                DB::table('menu_visibility_settings')->where('id', $existing->id)->update($payload);
                continue;
            }

            DB::table('menu_visibility_settings')->insert(array_merge($payload, $this->onlyExistingColumns($columns, [
                'created_at' => now(),
            ])));
        }
    }

    private function onlyExistingColumns(array $columns, array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $key) => in_array($key, $columns, true))
            ->all();
    }

    private function expandedAliases(array $aliases): array
    {
        $expanded = [];

        foreach ($aliases as $alias) {
            $normalized = Str::slug(str_replace(['.', '_', '/', '\\'], '-', (string) $alias));
            if ($normalized === '') {
                continue;
            }

            $expanded[] = $normalized;

            if (str_ends_with($normalized, 's') && strlen($normalized) > 3) {
                $expanded[] = rtrim($normalized, 's');
            }

            if (! str_ends_with($normalized, 's')) {
                $expanded[] = $normalized . 's';
            }
        }

        return array_values(array_unique(array_filter($expanded)));
    }
};
