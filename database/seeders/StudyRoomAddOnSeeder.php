<?php

namespace Database\Seeders;

use App\Models\AddOn;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class StudyRoomAddOnSeeder extends Seeder
{
    public function run(): void
    {
        $basePath = base_path('packages/studyroomtechlab');

        if (! File::isDirectory($basePath)) {
            return;
        }

        foreach (File::directories($basePath) as $modulePath) {
            $jsonPath = $modulePath . DIRECTORY_SEPARATOR . 'module.json';

            if (! File::exists($jsonPath)) {
                continue;
            }

            $data = json_decode(File::get($jsonPath), true);

            if (empty($data['name'])) {
                continue;
            }

            AddOn::updateOrCreate(
                ['module' => $data['name']],
                [
                    'name' => $data['alias'] ?? $data['name'],
                    'monthly_price' => $data['monthly_price'] ?? 0,
                    'yearly_price' => $data['yearly_price'] ?? 0,
                    'package_name' => $data['package_name'] ?? null,
                    'for_admin' => (bool) ($data['for_admin'] ?? false),
                    'priority' => $data['priority'] ?? 0,
                    'is_enable' => 1,
                ]
            );
        }
    }
}
