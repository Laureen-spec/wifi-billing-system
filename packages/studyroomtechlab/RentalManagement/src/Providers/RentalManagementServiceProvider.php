<?php

namespace StudyRoomTechLab\RentalManagement\Providers;

use Illuminate\Support\ServiceProvider;

class RentalManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
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

        $viewsPath = __DIR__ . '/../../resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'rental-management');
        }
    }
}
