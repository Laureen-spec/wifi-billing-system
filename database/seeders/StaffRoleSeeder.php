<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class StaffRoleSeeder extends Seeder
{
    public function run(): void
    {
        User::where('type', 'company')
            ->select('id')
            ->get()
            ->each(fn (User $company) => User::ensureDefaultStaffProfiles($company->id));
    }
}
