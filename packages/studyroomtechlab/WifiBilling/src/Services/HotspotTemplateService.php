<?php

namespace StudyRoomTechLab\WifiBilling\Services;

use App\Models\WifiBillingHotspotTemplateSetting;

class HotspotTemplateService
{
    public function settingForIsp(int $ispId): WifiBillingHotspotTemplateSetting
    {
        $setting = WifiBillingHotspotTemplateSetting::query()->firstOrNew(['isp_id' => $ispId]);

        if (! $setting->exists) {
            $setting->forceFill($this->defaults());
        }

        return $setting;
    }

    public function payloadForIsp(int $ispId): array
    {
        $setting = $this->settingForIsp($ispId);

        return array_merge($this->defaults(), $setting->toArray(), [
            'id' => $setting->id,
            'isp_id' => $ispId,
            'logo_url' => $setting->logo_path ? asset('storage/' . ltrim($setting->logo_path, '/')) : null,
            'background_url' => $setting->background_path ? asset('storage/' . ltrim($setting->background_path, '/')) : null,
        ]);
    }

    public function saveForIsp(int $ispId, array $data): WifiBillingHotspotTemplateSetting
    {
        $setting = $this->settingForIsp($ispId);
        $setting->forceFill(array_merge($this->defaults(), $setting->exists ? $setting->toArray() : [], $data, [
            'isp_id' => $ispId,
        ]));
        $setting->save();

        return $setting;
    }

    public function defaults(): array
    {
        return [
            'template_key' => 'modern',
            'template_name' => 'Modern Hotspot',
            'logo_path' => null,
            'background_path' => null,
            'primary_color' => '#0f766e',
            'secondary_color' => '#0f172a',
            'accent_color' => '#f59e0b',
            'welcome_text' => 'Welcome to our WiFi hotspot',
            'footer_text' => 'Powered by StudyRoom WiFi Billing',
            'care_phone' => null,
            'redirect_url' => null,
            'language' => 'en',
            'purchase_instructions' => [
                'Choose a package',
                'Enter your M-Pesa phone number',
                'Confirm the STK prompt',
                'Internet activates after payment confirmation',
            ],
            'custom_css' => null,
            'enable_datalan_free_access' => false,
            'free_access_duration_minutes' => 60,
            'free_access_cooldown_hours' => 24,
            'free_access_package_id' => null,
            'free_access_speed_limit' => null,
            'free_access_identity_mode' => 'mac',
            'free_access_requires_phone' => false,
            'free_access_requires_name' => false,
            'free_access_button_text' => 'Get 1 hour free access',
            'free_access_cooldown_message' => 'You already used free access. Come back after @time_remaining.',
            'free_access_success_message' => 'Free access is active for @duration minutes.',
        ];
    }

    public function templateOptions(): array
    {
        return [
            ['value' => 'simple', 'label' => 'Simple'],
            ['value' => 'modern', 'label' => 'Modern'],
            ['value' => 'datalan_free_access', 'label' => 'DataLAN Free Access'],
            ['value' => 'custom', 'label' => 'Custom'],
        ];
    }

    public function languageOptions(): array
    {
        return [
            ['value' => 'en', 'label' => 'English'],
            ['value' => 'sw', 'label' => 'Swahili'],
            ['value' => 'fr', 'label' => 'French'],
        ];
    }
}
