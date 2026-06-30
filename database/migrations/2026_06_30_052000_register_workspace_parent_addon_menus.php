<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $menus = [
        [
            'menu_key' => 'isp-sms',
            'label' => 'ISP SMS',
            'menu_group' => 'StudyRoom Add-ons',
            'route_name' => 'isp.sms.index',
            'url' => '/isp/sms',
            'sort_order' => 64,
            'aliases' => ['IspSms', 'ISP SMS', 'sms', 'isp sms'],
        ],
        [
            'menu_key' => 'isp-report',
            'label' => 'ISP Report',
            'menu_group' => 'StudyRoom Add-ons',
            'route_name' => 'isp-reports.index',
            'url' => '/wifi-billing/isp-reports',
            'sort_order' => 66,
            'aliases' => ['IspReport', 'ISP Report', 'reports', 'isp reports'],
        ],
        [
            'menu_key' => 'tr069',
            'label' => 'TR069',
            'menu_group' => 'StudyRoom Add-ons',
            'route_name' => 'tr069.index',
            'url' => '/tr069',
            'sort_order' => 67,
            'aliases' => ['Tr069', 'TR069', 'tr-069', 'cpe manager'],
        ],
        [
            'menu_key' => 'loyalty',
            'label' => 'Loyalty',
            'menu_group' => 'StudyRoom Add-ons',
            'route_name' => 'loyalty.index',
            'url' => '/loyalty',
            'sort_order' => 68,
            'aliases' => ['Loyalty', 'rewards'],
        ],
        [
            'menu_key' => 'isp-payment-center',
            'label' => 'Payment Center',
            'menu_group' => 'StudyRoom Add-ons',
            'route_name' => 'isp-payment-center.index',
            'url' => '/isp-payment-center',
            'sort_order' => 65,
            'aliases' => ['IspPaymentCenter', 'payment center', 'payments'],
        ],
        [
            'menu_key' => 'expenses',
            'label' => 'Expenses',
            'menu_group' => 'StudyRoom Add-ons',
            'route_name' => 'expenses.index',
            'url' => '/expenses',
            'sort_order' => 58,
            'aliases' => ['Expenses', 'expense'],
        ],
    ];

    public function up(): void
    {
        $this->registerMenuVisibilityDefaults();
        $this->clearMenuCache();
    }

    public function down(): void
    {
        // These are normal menu defaults, same as WiFi Billing. Do not delete on rollback.
    }

    private function registerMenuVisibilityDefaults(): void
    {
        if (! Schema::hasTable('menu_visibility_settings')) {
            return;
        }

        foreach ($this->menus as $menu) {
            $record = [];
            $this->setColumn($record, 'label', $menu['label']);
            $this->setColumn($record, 'menu_group', $menu['menu_group']);
            $this->setColumn($record, 'route_name', $menu['route_name']);
            $this->setColumn($record, 'url', $menu['url']);
            $this->setColumn($record, 'aliases', json_encode($menu['aliases']));
            $this->setColumn($record, 'sort_order', $menu['sort_order']);
            $this->setColumn($record, 'is_system', 1);
            $this->setColumn($record, 'visible_to_superadmin', 1);
            $this->setColumn($record, 'visible_to_admin', 1);
            $this->setColumn($record, 'visible_to_isp_admin', 1);
            $this->setColumn($record, 'block_route_access', 0);
            $this->setColumn($record, 'updated_at', now());

            $exists = DB::table('menu_visibility_settings')
                ->where('menu_key', $menu['menu_key'])
                ->exists();

            if (! $exists) {
                $this->setColumn($record, 'created_at', now());
            }

            DB::table('menu_visibility_settings')->updateOrInsert(
                ['menu_key' => $menu['menu_key']],
                $record
            );
        }
    }

    private function setColumn(array &$record, string $column, mixed $value): void
    {
        if (Schema::hasColumn('menu_visibility_settings', $column)) {
            $record[$column] = $value;
        }
    }

    private function clearMenuCache(): void
    {
        foreach (['menu_visibility_settings', 'menu_visibility_payload', 'module_menu_visibility'] as $key) {
            Cache::forget($key);
        }
    }
};
