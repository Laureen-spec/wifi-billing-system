<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class DemoStaffSeeder extends Seeder
{
    public function run($userId)
    {
        if (User::where('created_by', $userId)->where('type','staff')->count() > 2) {
            return;
        }

        $faker = Faker::create();

        $users = [
            ['name' => 'John Smith', 'email' => 'john.smith@company.com', 'type' => 'staff', 'role' => 'staff', 'avatar' => 'boys1.png'],
            ['name' => 'Sarah Johnson', 'email' => 'sarah.johnson@client.com', 'type' => 'client', 'role' => 'client', 'avatar' => 'girls1.png'],
            ['name' => 'Michael Brown', 'email' => 'michael.brown@company.com', 'type' => 'staff', 'role' => 'staff', 'avatar' => 'boys2.png'],
            ['name' => 'Emily Davis', 'email' => 'emily.davis@client.com', 'type' => 'client', 'role' => 'client', 'avatar' => 'girls2.png'],
            ['name' => 'David Wilson', 'email' => 'david.wilson@company.com', 'type' => 'staff', 'role' => 'staff', 'avatar' => 'boys3.png'],
            ['name' => 'Lisa Anderson', 'email' => 'lisa.anderson@client.com', 'type' => 'client', 'role' => 'client', 'avatar' => 'girls3.png'],
            ['name' => 'Robert Taylor', 'email' => 'robert.taylor@company.com', 'type' => 'staff', 'role' => 'staff', 'avatar' => 'boys4.png'],
            ['name' => 'Jennifer Martinez', 'email' => 'jennifer.martinez@client.com', 'type' => 'client', 'role' => 'client', 'avatar' => 'girls4.png'],
            ['name' => 'James Garcia', 'email' => 'james.garcia@company.com', 'type' => 'staff', 'role' => 'staff', 'avatar' => 'boys5.png'],
            ['name' => 'Maria Rodriguez', 'email' => 'maria.rodriguez@client.com', 'type' => 'client', 'role' => 'client', 'avatar' => 'girls5.png'],
            ['name' => 'Christopher Lee', 'email' => 'christopher.lee@company.com', 'type' => 'staff', 'role' => 'staff', 'avatar' => 'boys6.png'],
            ['name' => 'Amanda White', 'email' => 'amanda.white@client.com', 'type' => 'client', 'role' => 'client', 'avatar' => 'girls6.png'],
            ['name' => 'Daniel Thompson', 'email' => 'daniel.thompson@company.com', 'type' => 'staff', 'role' => 'staff', 'avatar' => 'boys7.png'],
            ['name' => 'Jessica Harris', 'email' => 'jessica.harris@client.com', 'type' => 'client', 'role' => 'client', 'avatar' => 'girls7.png'],
            ['name' => 'Matthew Clark', 'email' => 'matthew.clark@company.com', 'type' => 'staff', 'role' => 'staff', 'avatar' => 'boys8.png'],
            ['name' => 'Ashley Lewis', 'email' => 'ashley.lewis@client.com', 'type' => 'client', 'role' => 'client', 'avatar' => 'girls8.png'],
            ['name' => 'Anthony Walker', 'email' => 'anthony.walker@company.com', 'type' => 'staff', 'role' => 'staff', 'avatar' => 'boys9.png'],
            ['name' => 'Michelle Hall', 'email' => 'michelle.hall@client.com', 'type' => 'client', 'role' => 'client', 'avatar' => 'girls9.png'],
            ['name' => 'Mark Allen', 'email' => 'mark.allen@company.com', 'type' => 'staff', 'role' => 'staff', 'avatar' => 'boys10.png'],
            ['name' => 'Nicole Young', 'email' => 'nicole.young@client.com', 'type' => 'client', 'role' => 'client', 'avatar' => 'girls10.png'],
            ['name' => 'Alex Vendor', 'email' => 'alex.vendor@supplier.com', 'type' => 'vendor', 'role' => 'vendor', 'avatar' => 'boys11.png'],
            ['name' => 'Sam Supplier', 'email' => 'sam.supplier@vendor.com', 'type' => 'vendor', 'role' => 'vendor', 'avatar' => 'boys12.png'],
            ['name' => 'Tech Solutions Inc', 'email' => 'contact@techsolutions.com', 'type' => 'vendor', 'role' => 'vendor', 'avatar' => 'boys13.png'],
            ['name' => 'Global Supplies Co', 'email' => 'info@globalsupplies.com', 'type' => 'vendor', 'role' => 'vendor', 'avatar' => 'boys14.png'],
            ['name' => 'Prime Materials Ltd', 'email' => 'sales@primematerials.com', 'type' => 'vendor', 'role' => 'vendor', 'avatar' => 'boys15.png'],
            ['name' => 'Elite Vendors Group', 'email' => 'orders@elitevendors.com', 'type' => 'vendor', 'role' => 'vendor', 'avatar' => 'boys16.png'],
            ['name' => 'Quality Parts Corp', 'email' => 'support@qualityparts.com', 'type' => 'vendor', 'role' => 'vendor', 'avatar' => 'boys17.png'],
            ['name' => 'Swift Logistics', 'email' => 'dispatch@swiftlogistics.com', 'type' => 'vendor', 'role' => 'vendor', 'avatar' => 'boys18.png'],
            ['name' => 'Mega Distributors', 'email' => 'wholesale@megadist.com', 'type' => 'vendor', 'role' => 'vendor', 'avatar' => 'boys19.png'],
            ['name' => 'Pro Equipment Ltd', 'email' => 'rentals@proequipment.com', 'type' => 'vendor', 'role' => 'vendor', 'avatar' => 'boys20.png'],
            ['name' => 'Smart Systems Inc', 'email' => 'tech@smartsystems.com', 'type' => 'vendor', 'role' => 'vendor', 'avatar' => 'boys21.png'],
            ['name' => 'Reliable Resources', 'email' => 'procurement@reliable.com', 'type' => 'vendor', 'role' => 'vendor', 'avatar' => 'boys22.png'],
            ['name' => 'Advanced Materials', 'email' => 'orders@advancedmat.com', 'type' => 'vendor', 'role' => 'vendor', 'avatar' => 'boys23.png'],
            ['name' => 'Express Suppliers', 'email' => 'express@suppliers.com', 'type' => 'vendor', 'role' => 'vendor', 'avatar' => 'boys24.png'],
            ['name' => 'Industrial Partners', 'email' => 'partners@industrial.com', 'type' => 'vendor', 'role' => 'vendor', 'avatar' => 'boys25.png'],
            ['name' => 'ABC Corporation', 'email' => 'contact@abccorp.com', 'type' => 'client', 'role' => 'client', 'avatar' => 'girls11.png'],
            ['name' => 'XYZ Industries', 'email' => 'info@xyzind.com', 'type' => 'client', 'role' => 'client', 'avatar' => 'girls12.png'],
            ['name' => 'Global Solutions Ltd', 'email' => 'sales@globalsol.com', 'type' => 'client', 'role' => 'client', 'avatar' => 'girls13.png'],
            ['name' => 'Tech Innovations Inc', 'email' => 'hello@techinno.com', 'type' => 'client', 'role' => 'client', 'avatar' => 'girls14.png'],
            ['name' => 'Prime Services Co', 'email' => 'support@primeserv.com', 'type' => 'client', 'role' => 'client', 'avatar' => 'girls15.png'],
            ['name' => 'Elite Enterprises', 'email' => 'admin@eliteent.com', 'type' => 'client', 'role' => 'client', 'avatar' => 'girls16.png'],
            ['name' => 'Smart Systems Corp', 'email' => 'contact@smartsys.com', 'type' => 'client', 'role' => 'client', 'avatar' => 'girls17.png'],
            ['name' => 'Dynamic Solutions', 'email' => 'info@dynsol.com', 'type' => 'client', 'role' => 'client', 'avatar' => 'girls18.png'],
            ['name' => 'Future Tech Ltd', 'email' => 'hello@futuretech.com', 'type' => 'client', 'role' => 'client', 'avatar' => 'girls19.png'],
            ['name' => 'Innovative Corp', 'email' => 'contact@innovcorp.com', 'type' => 'client', 'role' => 'client', 'avatar' => 'girls20.png'],
            ['name' => 'Advanced Systems', 'email' => 'support@advsys.com', 'type' => 'client', 'role' => 'client', 'avatar' => 'girls21.png'],
            ['name' => 'Professional Services', 'email' => 'info@proserv.com', 'type' => 'client', 'role' => 'client', 'avatar' => 'girls22.png'],
            ['name' => 'Quality Solutions Inc', 'email' => 'sales@qualsol.com', 'type' => 'client', 'role' => 'client', 'avatar' => 'girls23.png'],
            ['name' => 'Reliable Partners', 'email' => 'contact@relpart.com', 'type' => 'client', 'role' => 'client', 'avatar' => 'girls24.png'],
            ['name' => 'Strategic Consulting', 'email' => 'hello@stratcon.com', 'type' => 'client', 'role' => 'client', 'avatar' => 'girls25.png'],
        ];

        foreach ($users as $index => $userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'avatar' => $userData['avatar'],
                'email_verified_at' => now(),
                'password' => Hash::make('1234'),
                'mobile_no' => '+' . $faker->numberBetween(1, 999) . $faker->numerify('##########'),
                'type' => $userData['type'],
                'creator_id' => $userId,
                'created_by' => $userId,
            ]);

            $user->assignRole($userData['role']);
        }
    }
}
