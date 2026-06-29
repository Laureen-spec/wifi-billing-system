<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use StudyRoomTechLab\LandingPage\Database\Seeders\LandingPageSettingSeeder;
use StudyRoomTechLab\LandingPage\Models\LandingPageSetting;

class ResetLandingPageContent extends Command
{
    protected $signature = 'landing-page:studyroom-content {--force : Replace current landing page content without confirmation}';

    protected $description = 'Replace landing page content with StudyRoom TechLab WiFi Billing website content.';

    public function handle(): int
    {
        if (! Schema::hasTable('landing_page_settings')) {
            $this->warn('landing_page_settings table was not found. Running migrations first...');
            Artisan::call('migrate', ['--force' => true]);
            $this->output->write(Artisan::output());
        }

        if (! $this->option('force') && ! $this->confirm('This will replace the current landing page content. Continue?')) {
            $this->info('Cancelled.');
            return self::SUCCESS;
        }

        $settings = LandingPageSettingSeeder::defaultSettings();

        LandingPageSetting::updateOrCreate(
            ['id' => 1],
            $settings
        );

        Cache::forget('landing_page_settings');

        $this->info('StudyRoom TechLab WiFi Billing landing page content has been applied.');
        $this->line('Open the public website route and refresh after clearing browser cache.');

        return self::SUCCESS;
    }
}
