<?php

namespace StudyRoomTechLab\WifiBilling\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use StudyRoomTechLab\WifiBilling\Console\Commands\RetryPendingProvisioningCommand;

class WifiBillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([
            RetryPendingProvisioningCommand::class,
        ]);
    }

    public function boot(): void
    {
        $viewsPath = __DIR__ . '/../../resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'wifi-billing');
        }

        $routesPath = __DIR__ . '/../Routes/web.php';
        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }

        $migrationsPath = __DIR__ . '/../Database/Migrations';
        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('wifi-billing:retry-pending-provisioning')->everyMinute()->withoutOverlapping();
        });
    }
}
