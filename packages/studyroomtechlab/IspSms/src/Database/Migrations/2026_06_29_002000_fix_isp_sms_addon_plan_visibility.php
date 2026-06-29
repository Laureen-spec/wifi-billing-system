<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        if (Schema::hasTable('add_ons')) {
            $addon = [
                'module' => 'IspSms',
                'name' => 'ISP SMS',
                'monthly_price' => 0,
                'yearly_price' => 0,
                'is_enable' => 1,
                'for_admin' => 0,
                'package_name' => 'isp-sms',
                'priority' => 55,
                'updated_at' => $now,
            ];

            if (Schema::hasColumn('add_ons', 'created_at')) {
                $addon['created_at'] = $now;
            }

            $addon = $this->onlyExistingColumns('add_ons', $addon);

            $query = DB::table('add_ons')->where(function ($query) {
                $query->where('module', 'IspSms')
                    ->orWhere('package_name', 'isp-sms')
                    ->orWhere('name', 'ISP SMS');
            });

            if ((clone $query)->exists()) {
                unset($addon['created_at']);
                $query->update($addon);
            } else {
                DB::table('add_ons')->insert($addon);
            }
        }

        if (Schema::hasTable('menu_visibility_settings')) {
            $metadata = [
                'label' => 'ISP SMS',
                'menu_group' => 'WiFi Billing',
                'parent_key' => 'wifi-billing',
                'route_name' => 'isp.sms.index',
                'url' => '/isp/sms',
                'aliases' => json_encode([
                    'isp-sms',
                    'IspSms',
                    'ISP SMS',
                    'isp.sms.index',
                    'isp.sms.create',
                    'isp.sms.new-message',
                    'isp.sms.new-message.send',
                    'isp.sms.settings',
                    'isp.sms.templates.index',
                    '/isp/sms',
                    '/isp/sms/create',
                    '/isp/sms/new-message',
                    'sms',
                    'sms-messages',
                    'sms-settings',
                    'sms-templates',
                    'new-sms-message',
                ]),
                'sort_order' => 56,
                'is_system' => 1,
                'updated_at' => $now,
            ];

            $metadata = $this->onlyExistingColumns('menu_visibility_settings', $metadata);

            $existing = DB::table('menu_visibility_settings')
                ->where('menu_key', 'isp-sms')
                ->first();

            if ($existing) {
                DB::table('menu_visibility_settings')
                    ->where('menu_key', 'isp-sms')
                    ->update($metadata);
            } else {
                $defaults = [
                    'menu_key' => 'isp-sms',
                    'visible_to_superadmin' => 1,
                    'visible_to_admin' => 1,
                    'visible_to_isp_admin' => 1,
                    'block_route_access' => 0,
                    'created_at' => $now,
                ];

                DB::table('menu_visibility_settings')->insert(
                    $this->onlyExistingColumns('menu_visibility_settings', array_merge($defaults, $metadata))
                );
            }
        }
    }

    public function down(): void
    {
        // Data-only safety migration. Do not disable or remove the installed add-on on rollback.
    }

    private function onlyExistingColumns(string $table, array $values): array
    {
        return array_filter(
            $values,
            fn (string $column): bool => Schema::hasColumn($table, $column),
            ARRAY_FILTER_USE_KEY
        );
    }
};
