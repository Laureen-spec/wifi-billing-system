<?php

namespace StudyRoomTechLab\Tr069\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use StudyRoomTechLab\Tr069\Models\Tr069Profile;
use StudyRoomTechLab\Tr069\Models\Tr069Setting;
use StudyRoomTechLab\Tr069\Services\Tr069Manager;

class Tr069SettingsController extends Tr069Controller
{
    public function edit(Request $request, Tr069Manager $manager): Response
    {
        $this->authorizeAccess($request, true);

        $isp = $this->resolveIsp($request, $request->integer('isp_id') ?: null);
        $setting = $manager->getSettings($isp->id);

        return Inertia::render('tr069/settings', [
            'isp' => ['id' => $isp->id, 'name' => $isp->name],
            'isPlatform' => $this->isPlatform($request),
            'isps' => $this->ispOptions($request),
            'setting' => $this->settingPayload($setting),
            'profileOptions' => $this->profileOptions($request),
            'saveUrl' => route('tr069.settings.save', $this->isPlatform($request) ? ['isp_id' => $isp->id] : []),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeAccess($request, true);

        $data = $request->validate([
            'isp_id' => ['nullable', 'integer', 'exists:isps,id'],
            'enabled' => ['nullable', 'boolean'],
            'acs_url' => ['nullable', 'url', 'max:2000'],
            'api_token' => ['nullable', 'string', 'max:255'],
            'inform_interval' => ['nullable', 'integer', 'min:60', 'max:604800'],
            'connection_request_username' => ['nullable', 'string', 'max:255'],
            'connection_request_password' => ['nullable', 'string', 'max:255'],
            'default_profile_id' => ['nullable', 'integer', 'exists:tr069_profiles,id'],
            'allow_auto_register' => ['nullable', 'boolean'],
            'require_known_serial' => ['nullable', 'boolean'],
        ]);

        $ispId = $this->tenantIdForWrite($request);
        $setting = Tr069Setting::query()->firstOrCreate(
            ['isp_id' => $ispId],
            Tr069Setting::defaults($ispId, $this->companyIdForWrite($request))
        );

        foreach (['api_token', 'connection_request_password'] as $field) {
            if (($data[$field] ?? null) === '********') {
                unset($data[$field]);
            }
        }

        $setting->update(array_merge($data, [
            'isp_id' => $ispId,
            'company_id' => $this->companyIdForWrite($request),
            'enabled' => $request->boolean('enabled'),
            'allow_auto_register' => $request->boolean('allow_auto_register'),
            'require_known_serial' => $request->boolean('require_known_serial'),
        ]));

        return redirect()
            ->route('tr069.settings', $this->isPlatform($request) ? ['isp_id' => $ispId] : [])
            ->with('success', 'TR069 settings saved.');
    }

    private function profileOptions(Request $request): array
    {
        return $this->scopedToTenant(Tr069Profile::query(), $request, 'tr069_profiles')
            ->where('status', Tr069Profile::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Tr069Profile $profile): array => ['id' => $profile->id, 'name' => $profile->name])
            ->values()
            ->all();
    }

    private function settingPayload(Tr069Setting $setting): array
    {
        return [
            'id' => $setting->id,
            'isp_id' => $setting->isp_id,
            'enabled' => (bool) $setting->enabled,
            'acs_url' => $setting->acs_url,
            'api_token' => $this->mask($setting->api_token),
            'inform_interval' => $setting->inform_interval,
            'connection_request_username' => $setting->connection_request_username,
            'connection_request_password' => $this->mask($setting->connection_request_password),
            'default_profile_id' => $setting->default_profile_id,
            'allow_auto_register' => (bool) $setting->allow_auto_register,
            'require_known_serial' => (bool) $setting->require_known_serial,
        ];
    }
}
