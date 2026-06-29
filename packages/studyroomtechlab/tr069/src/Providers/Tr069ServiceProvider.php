<?php

namespace StudyRoomTechLab\Tr069\Providers;

use Illuminate\Support\ServiceProvider;

class Tr069ServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach (['web.php', 'api.php'] as $file) {
            $path = __DIR__ . '/../Routes/' . $file;
            if (is_file($path)) {
                $this->loadRoutesFrom($path);
            }
        }

        $migrationsPath = __DIR__ . '/../Database/Migrations';
        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }
}
