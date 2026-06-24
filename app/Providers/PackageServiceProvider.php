<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class PackageServiceProvider extends ServiceProvider
{
    private array $packageRoots = [
        'packages/workdo',
        'packages/studyroomtechlab',
    ];

    public function register(): void
    {
        $loader = require base_path('vendor/autoload.php');

        $packageDirectories = [];
        foreach ($this->packageRoots as $packageRoot) {
            $packageDirectories = array_merge(
                $packageDirectories,
                glob(base_path($packageRoot . '/*'), GLOB_ONLYDIR) ?: []
            );
        }

        foreach ($packageDirectories as $packageDir) {
            $composerFile = $packageDir . '/composer.json';

            if (file_exists($composerFile)) {
                $composerConfig = json_decode(file_get_contents($composerFile), true);

                if (isset($composerConfig['autoload']['psr-4'])) {
                    foreach ($composerConfig['autoload']['psr-4'] as $namespace => $path) {
                        $loader->addPsr4($namespace, $packageDir . '/' . $path);
                    }
                }

                if (isset($composerConfig['extra']['laravel']['providers'])) {
                    foreach ($composerConfig['extra']['laravel']['providers'] as $provider) {
                        $this->app->register($provider);
                    }
                }
            }
        }
    }

    public function boot(): void
    {
        //
    }
}
