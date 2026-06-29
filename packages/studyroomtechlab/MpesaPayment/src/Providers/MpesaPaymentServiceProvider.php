<?php

namespace StudyRoomTechLab\MpesaPayment\Providers;

use Illuminate\Support\ServiceProvider;

class MpesaPaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $configPath = __DIR__ . '/../../config/mpesa-payment.php';

        if (is_file($configPath)) {
            $this->mergeConfigFrom($configPath, 'mpesa-payment');
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

        $viewsPath = __DIR__ . '/../../resources/views';

        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'mpesa-payment');
        }

        $configPath = __DIR__ . '/../../config/mpesa-payment.php';

        if (is_file($configPath)) {
            $this->publishes([
                $configPath => config_path('mpesa-payment.php'),
            ], 'mpesa-payment-config');
        }
    }
}