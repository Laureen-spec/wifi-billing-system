<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('type','superadmin')->first();

        if (User::where('created_by', $admin->id)->where('type','company')->count() > 2) {
            return;
        }

        $faker = Faker::create();

        $users = [
            ['name' => 'Tech Solutions Inc', 'email' => 'admin@techsolutions.com', 'avatar' => 'company-image2.png'],
            ['name' => 'Global Marketing Corp', 'email' => 'contact@globalmarketing.com', 'avatar' => 'company-image3.png'],
            ['name' => 'Digital Innovations LLC', 'email' => 'info@digitalinnovations.com', 'avatar' => 'company-image4.png'],
            ['name' => 'Creative Design Studio', 'email' => 'hello@creativedesign.com', 'avatar' => 'company-image5.png'],
            ['name' => 'Business Consulting Group', 'email' => 'support@businessconsulting.com', 'avatar' => 'company-image6.png']
        ];

        foreach ($users as $index => $userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'avatar' => $userData['avatar'],
                'mobile_no' => '+' . $faker->numberBetween(1, 999) . $faker->numerify('##########'),
                'password' => Hash::make('1234'),
                'type' => 'company',
                'lang' => 'en',
                'email_verified_at' => now(),
                'creator_id' => $admin->id,
                'created_by' => $admin->id
            ]);

            $user->assignRole('company');
            User::CompanySetting($user->id);
            // Make Company's role
            User::MakeRole($user->id);
        }
    }
}
