<?php

namespace StudyRoomTechLab\IspPaymentCenter\Providers;

use Illuminate\Support\ServiceProvider;

class IspPaymentCenterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $routesPath = __DIR__ . '/../Routes/web.php';

        if (is_file($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }

        $migrationsPath = __DIR__ . '/../Database/Migrations';

        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }
}
