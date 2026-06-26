<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        (new DefaultLoginUserSeeder())->run();
        (new PermissionRoleSeeder())->run();
        (new DefaultLoginUserSeeder())->run();
        (new StaffRoleSeeder())->run();
        (new IspRoleSeeder())->run();
        (new DefultSetting())->run();
        (new PlanSeeder())->run();
        (new EmailTemplatesSeeder())->run();
        (new NotificationsTableSeeder())->run();
        (new StudyRoomAddOnSeeder())->run();

        $company = User::where('email', 'company@example.com')->first();
        $userId = $company?->id;
        if ($company) {
            User::CompanySetting($company->id);
        }

        if(config('app.run_demo_seeder'))
        {
            // // Pass $userId to your custom seeder


            (new CouponSeeder())->run();
            (new DemoUserSeeder())->run();

            (new DemoStaffSeeder())->run($userId);
            (new DemoLoginHistorySeeder())->run($userId);
            (new DemoWarehouseSeeder())->run($userId);
            (new HelpdeskCategorySeeder())->run();
            (new HelpdeskTicketSeeder())->run($userId);
            (new HelpdeskReplySeeder())->run($userId);
            (new DemoOrderSeeder())->run($userId);
            (new DemoCouponDetailsSeeder())->run();
            (new DemoBankTransferSeeder())->run($userId);
            (new MessengerSeeder())->run();
            (new AIAgentChatSessionSeeder())->run($userId);
            (new AIAgentChatMessageSeeder())->run($userId);

             // temporary
            // (new PackageSeeder())->run($userId);

            // in this seeder product
            (new DemoTransferSeeder())->run($userId);
        }
    }
}
