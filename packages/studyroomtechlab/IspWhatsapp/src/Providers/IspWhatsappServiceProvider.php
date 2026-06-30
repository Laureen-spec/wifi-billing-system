<?php

namespace StudyRoomTechLab\IspWhatsapp\Providers;

use Illuminate\Support\ServiceProvider;

class IspWhatsappServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $configPath = __DIR__ . '/../../config/isp-whatsapp.php';

        if (is_file($configPath)) {
            $this->mergeConfigFrom($configPath, 'isp-whatsapp');
        }
    }

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

        $configPath = __DIR__ . '/../../config/isp-whatsapp.php';
        if (is_file($configPath)) {
            $this->publishes([
                $configPath => config_path('isp-whatsapp.php'),
            ], 'isp-whatsapp-config');
        }
    }
}
