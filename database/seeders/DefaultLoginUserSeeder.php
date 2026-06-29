<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class DefaultLoginUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $columns = Schema::getColumnListing('users');

        $superAdmin = $this->updateOrCreateUser('superadmin@example.com', [
            'name' => 'Super Admin',
            'mobile_no' => '+133344455566',
            'email_verified_at' => now(),
            'password' => Hash::make('1234'),
            'type' => 'superadmin',
            'avatar' => 'avatar.png',
            'lang' => 'en',
            'layout_direction' => 'ltr',
            'active_plan' => null,
            'plan_expire_date' => null,
            'is_trial_done' => 0,
            'total_user' => -1,
            'storage_limit' => 0,
            'is_disable' => 0,
            'is_enable_login' => 1,
            'active_status' => 1,
            'creator_id' => null,
            'created_by' => null,
        ], $columns);

        $company = $this->updateOrCreateUser('company@example.com', [
            'name' => 'Company',
            'mobile_no' => '+122233344455',
            'email_verified_at' => now(),
            'password' => Hash::make('1234'),
            'type' => 'company',
            'avatar' => 'avatar.png',
            'lang' => 'en',
            'layout_direction' => 'ltr',
            'active_plan' => null,
            'plan_expire_date' => null,
            'is_trial_done' => 0,
            'total_user' => 0,
            'storage_limit' => 0,
            'is_disable' => 0,
            'is_enable_login' => 1,
            'active_status' => 1,
            'creator_id' => $superAdmin->id,
            'created_by' => $superAdmin->id,
        ], $columns);

        $this->assignRole($superAdmin, 'superadmin', 'Super Admin');
        $this->assignRole($company, 'company', 'Company', $superAdmin->id);
    }

    private function updateOrCreateUser(string $email, array $data, array $columns): User
    {
        $payload = array_intersect_key($data, array_flip($columns));

        return User::updateOrCreate(
            ['email' => $email],
            $payload
        );
    }

    private function assignRole(User $user, string $name, string $label, ?int $createdBy = null): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('model_has_roles')) {
            return;
        }

        $role = Role::updateOrCreate(
            ['name' => $name, 'guard_name' => 'web'],
            [
                'label' => $label,
                'editable' => false,
                'created_by' => $createdBy,
            ]
        );

        $user->syncRoles([$role]);
    }
}
