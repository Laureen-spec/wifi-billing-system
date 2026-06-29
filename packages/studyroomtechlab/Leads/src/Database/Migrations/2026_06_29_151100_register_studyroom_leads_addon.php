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
        $this->activateForCompanyAccounts();
    }

    public function down(): void
    {
        if (Schema::hasTable('add_ons') && Schema::hasColumn('add_ons', 'module')) {
            DB::table('add_ons')->where('module', 'Leads')->delete();
        }
    }

    private function syncAddonRow(): void
    {
        if (! Schema::hasTable('add_ons')) {
            return;
        }

        $record = [];
        $this->setValue('add_ons', $record, 'module', 'Leads');
        $this->setValue('add_ons', $record, 'name', 'Lead Desk');
        $this->setValue('add_ons', $record, 'alias', 'Lead Desk');
        $this->setValue('add_ons', $record, 'description', 'ISP prospect tracking, follow-ups, lead sources, and conversion to customers.');
        $this->setValue('add_ons', $record, 'package_name', 'leads');
        $this->setValue('add_ons', $record, 'is_enable', 1);
        $this->setValue('add_ons', $record, 'for_admin', 0);
        $this->setValue('add_ons', $record, 'display', 1);
        $this->setValue('add_ons', $record, 'status', 1);
        $this->setValue('add_ons', $record, 'priority', 51);
        $this->setValue('add_ons', $record, 'version', 1.0);
        $this->setValue('add_ons', $record, 'monthly_price', 0);
        $this->setValue('add_ons', $record, 'yearly_price', 0);
        $this->setValue('add_ons', $record, 'parent_module', json_encode(['WifiBilling']));
        $this->setValue('add_ons', $record, 'updated_at', now());

        if ($record === []) {
            return;
        }

        $query = DB::table('add_ons')->where(function ($query) {
            if (Schema::hasColumn('add_ons', 'module')) {
                $query->orWhere('module', 'Leads');
            }

            if (Schema::hasColumn('add_ons', 'package_name')) {
                $query->orWhere('package_name', 'leads');
            }

            if (Schema::hasColumn('add_ons', 'name')) {
                $query->orWhere('name', 'Lead Desk');
            }
        });

        if ($query->exists()) {
            $query->update($record);
            return;
        }

        $this->setValue('add_ons', $record, 'created_at', now());
        DB::table('add_ons')->insert($record);
    }

    private function syncMenuVisibilityRow(): void
    {
        if (! Schema::hasTable('menu_visibility_settings')) {
            return;
        }

        $record = [];
        $this->setValue('menu_visibility_settings', $record, 'menu_key', 'studyroom-leads');
        $this->setValue('menu_visibility_settings', $record, 'label', 'Lead Desk');
        $this->setValue('menu_visibility_settings', $record, 'menu_group', 'Operations');
        $this->setValue('menu_visibility_settings', $record, 'parent_key', 'lead-desk');
        $this->setValue('menu_visibility_settings', $record, 'route_name', 'studyroom-leads.index');
        $this->setValue('menu_visibility_settings', $record, 'url', '/lead-desk');
        $this->setValue('menu_visibility_settings', $record, 'aliases', json_encode([
            'Leads',
            'Lead Desk',
            'studyroom-leads',
            'studyroom-leads.index',
            '/lead-desk',
            'lead-desk',
            'isp-leads',
        ]));
        $this->setValue('menu_visibility_settings', $record, 'sort_order', 51);
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
            $query = DB::table('menu_visibility_settings')->where('menu_key', 'studyroom-leads');

            if ($query->exists()) {
                $query->update($record);
                return;
            }
        }

        $this->setValue('menu_visibility_settings', $record, 'created_at', now());
        DB::table('menu_visibility_settings')->insert($record);
    }

    private function activateForCompanyAccounts(): void
    {
        if (! Schema::hasTable('user_active_modules') || ! Schema::hasTable('users')) {
            return;
        }

        $userQuery = DB::table('users');
        if (Schema::hasColumn('users', 'type')) {
            $userQuery->whereIn('type', ['company', 'isp_admin']);
        }

        $userQuery->select('id')->orderBy('id')->chunkById(100, function ($users): void {
            foreach ($users as $user) {
                $record = [
                    'user_id' => $user->id,
                    'module' => 'Leads',
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
