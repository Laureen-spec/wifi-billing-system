<?php

namespace Workdo\Pos\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class PosDatabaseSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();

        $this->call(PermissionTableSeeder::class);
        $this->call(MarketplaceSettingSeeder::class);
        $this->call(EmailTemplatesSeeder::class);
        $this->call(NotificationsTableSeeder::class);

        if(config('app.run_demo_seeder'))
        {
            $userId = User::where('email', 'company@example.com')->first()->id;
            (new DemoPosSeeder())->run($userId);
            (new DemoPosBillingCounterSeeder())->run($userId);
            (new DemoPosDiscountSeeder())->run($userId);
            (new DemoPosReturnSeeder())->run($userId);
        }
    }
}