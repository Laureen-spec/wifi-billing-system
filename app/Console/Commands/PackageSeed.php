<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class PackageSeed extends Command
{
    protected $signature = 'package:seed {packageName?}';
    protected $description = 'Seed a specific package or all packages';

    public function handle()
    {
        $packageName = $this->argument('packageName');
        if ($packageName) {
            $this->seedPackage($packageName);
        } else {
            $this->seedAllPackages();
        }
    }

    protected function seedPackage($packageName)
    {
        $seederClass = $this->getSeederClass($packageName);

        if ($seederClass) {
            $this->info("Seeding {$packageName}...");
            Artisan::call('db:seed', ['--class' => $seederClass, '--force' => true]);
            $this->info("{$packageName} Seeder Run Successfully!");
        } else {
            $this->error("Seeder for package {$packageName} not found.");
        }
    }

    protected function seedAllPackages()
    {
        $packages = $this->getAllPackages();
        foreach ($packages as $package) {
            $this->seedPackage($package);
        }
    }

    protected function getSeederClass($packageName)
    {
        $seederClasses = [
            "Workdo\\{$packageName}\\Database\\Seeders\\{$packageName}DatabaseSeeder",
            "StudyRoomTechLab\\{$packageName}\\Database\\Seeders\\{$packageName}DatabaseSeeder",
        ];

        foreach ($seederClasses as $seederClass) {
            if (class_exists($seederClass)) {
                return $seederClass;
            }
        }

        return null;
    }

    protected function getAllPackages()
    {
        $packages = [];
        $vendorDirs = [
            base_path('packages/workdo'),
            base_path('packages/studyroomtechlab'),
        ];
        
        foreach ($vendorDirs as $vendorDir) {
            if (!File::exists($vendorDir)) {
                continue;
            }

            $directories = File::directories($vendorDir);
            foreach ($directories as $directory) {
                $packages[] = basename($directory);
            }
        }

        return array_unique($packages);
    }
}
