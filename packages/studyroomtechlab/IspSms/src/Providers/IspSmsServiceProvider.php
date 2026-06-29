<?php

namespace StudyRoomTechLab\IspSms\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use StudyRoomTechLab\IspSms\Console\Commands\SendQueuedSmsCommand;

class IspSmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $configPath = __DIR__ . '/../../config/isp-sms.php';

        if (is_file($configPath)) {
            $this->mergeConfigFrom($configPath, 'isp-sms');
        }

        $this->commands([
            SendQueuedSmsCommand::class,
        ]);
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
            $this->loadViewsFrom($viewsPath, 'isp-sms');
        }

        $configPath = __DIR__ . '/../../config/isp-sms.php';
        if (is_file($configPath)) {
            $this->publishes([
                $configPath => config_path('isp-sms.php'),
            ], 'isp-sms-config');
        }

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            $schedule
                ->command('isp-sms:send-queued')
                ->everyMinute()
                ->withoutOverlapping();
        });
    }
}
