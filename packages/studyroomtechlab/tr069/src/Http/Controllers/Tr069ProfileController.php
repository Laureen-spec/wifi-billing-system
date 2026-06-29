<?php

namespace StudyRoomTechLab\Tr069\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use StudyRoomTechLab\Tr069\Models\Tr069Profile;

class Tr069ProfileController extends Tr069Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeAccess($request);

        $filters = [
            'status' => $request->query('status', 'all'),
            'wan_mode' => $request->query('wan_mode', 'all'),
            'isp_id' => $request->integer('isp_id') ?: null,
        ];

        $query = $this->scopedToTenant(Tr069Profile::query()->with('isp'), $request, 'tr069_profiles');

        if (array_key_exists((string) $filters['status'], Tr069Profile::statuses())) {
            $query->where('status', $filters['status']);
        }

        if (array_key_exists((string) $filters['wan_mode'], Tr069Profile::wanModes())) {
            $query->where('wan_mode', $filters['wan_mode']);
        }

        return Inertia::render('tr069/profiles/index', [
            'isp' => $this->pageIsp($request),
            'isPlatform' => $this->isPlatform($request),
            'isps' => $this->ispOptions($request),
            'filters' => $filters,
            'profiles' => $query->latest()->paginate((int) $request->integer('per_page', 15))->withQueryString()
                ->through(fn (Tr069Profile $profile): array => $this->profilePayload($profile)),
            'statusOptions' => $this->optionList(['all' => 'All'] + Tr069Profile::statuses()),
            'wanModeOptions' => $this->optionList(['all' => 'All'] + Tr069Profile::wanModes()),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizeAccess($request, true);

        return Inertia::render('tr069/profiles/form', [
            'mode' => 'create',
            'profile' => $this->blankProfile($request),
            'isPlatform' => $this->isPlatform($request),
            'isps' => $this->ispOptions($request),
            'wanModeOptions' => $this->optionList(Tr069Profile::wanModes()),
            'statusOptions' => $this->optionList(Tr069Profile::statuses()),
            'storeUrl' => route('tr069.profiles.store'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAccess($request, true);

        $ispId = $this->tenantIdForWrite($request);
        $profile = Tr069Profile::query()->create(array_merge($this->validated($request), [
            'isp_id' => $ispId,
            'company_id' => $this->companyIdForWrite($request),
            'tr069_parameters' => $this->decodeJson($request->input('tr069_parameters_json'), 'TR069 parameters'),
            'wifi_enabled' => $request->boolean('wifi_enabled'),
        ]));

        return redirect()
            ->route('tr069.profiles.index', $this->isPlatform($request) ? ['isp_id' => $profile->isp_id] : [])
            ->with('success', 'TR069 provisioning profile created.');
    }

    public function edit(Request $request, Tr069Profile $profile): Response
    {
        $this->authorizeAccess($request, true);
        $this->authorizeIspRecord($request, $profile->isp_id);

        return Inertia::render('tr069/profiles/form', [
            'mode' => 'edit',
            'profile' => $this->profilePayload($profile),
            'isPlatform' => $this->isPlatform($request),
            'isps' => $this->ispOptions($request),
            'wanModeOptions' => $this->optionList(Tr069Profile::wanModes()),
            'statusOptions' => $this->optionList(Tr069Profile::statuses()),
            'updateUrl' => route('tr069.profiles.update', $profile),
        ]);
    }

    public function update(Request $request, Tr069Profile $profile): RedirectResponse
    {
        $this->authorizeAccess($request, true);
        $this->authorizeIspRecord($request, $profile->isp_id);

        $data = $this->validated($request);
        $ispId = $this->tenantIdForWrite($request);

        foreach (['pppoe_password', 'wifi_password'] as $field) {
            if (($data[$field] ?? null) === '********') {
                unset($data[$field]);
            }
        }

        $profile->update(array_merge($data, [
            'isp_id' => $ispId,
            'company_id' => $this->companyIdForWrite($request),
            'tr069_parameters' => $this->decodeJson($request->input('tr069_parameters_json'), 'TR069 parameters'),
            'wifi_enabled' => $request->boolean('wifi_enabled'),
        ]));

        return redirect()
            ->route('tr069.profiles.index', $this->isPlatform($request) ? ['isp_id' => $profile->isp_id] : [])
            ->with('success', 'TR069 provisioning profile updated.');
    }

    public function destroy(Request $request, Tr069Profile $profile): RedirectResponse
    {
        $this->authorizeAccess($request, true);
        $this->authorizeIspRecord($request, $profile->isp_id);

        $profile->delete();

        return back()->with('success', 'TR069 profile removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'isp_id' => ['nullable', 'integer', 'exists:isps,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'wan_mode' => ['required', 'string', Rule::in(array_keys(Tr069Profile::wanModes()))],
            'pppoe_username' => ['nullable', 'string', 'max:255'],
            'pppoe_password' => ['nullable', 'string', 'max:255'],
            'static_ip' => ['nullable', 'string', 'max:255'],
            'static_gateway' => ['nullable', 'string', 'max:255'],
            'static_dns' => ['nullable', 'string', 'max:255'],
            'vlan_id' => ['nullable', 'integer', 'min:1', 'max:4094'],
            'wifi_ssid' => ['nullable', 'string', 'max:255'],
            'wifi_password' => ['nullable', 'string', 'max:255'],
            'wifi_enabled' => ['nullable', 'boolean'],
            'tr069_parameters_json' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(array_keys(Tr069Profile::statuses()))],
        ]);
    }

    private function blankProfile(Request $request): array
    {
        return [
            'id' => null,
            'isp_id' => $this->isPlatform($request) ? ($request->integer('isp_id') ?: null) : $this->resolveIsp($request)->id,
            'name' => '',
            'description' => '',
            'wan_mode' => 'dhcp',
            'pppoe_username' => '',
            'pppoe_password' => '',
            'static_ip' => '',
            'static_gateway' => '',
            'static_dns' => '',
            'vlan_id' => '',
            'wifi_ssid' => '',
            'wifi_password' => '',
            'wifi_enabled' => true,
            'tr069_parameters_json' => '',
            'status' => Tr069Profile::STATUS_ACTIVE,
        ];
    }

    private function profilePayload(Tr069Profile $profile): array
    {
        return [
            'id' => $profile->id,
            'isp_id' => $profile->isp_id,
            'name' => $profile->name,
            'description' => $profile->description,
            'wan_mode' => $profile->wan_mode,
            'pppoe_username' => $profile->pppoe_username,
            'pppoe_password' => $this->mask($profile->pppoe_password),
            'static_ip' => $profile->static_ip,
            'static_gateway' => $profile->static_gateway,
            'static_dns' => $profile->static_dns,
            'vlan_id' => $profile->vlan_id,
            'wifi_ssid' => $profile->wifi_ssid,
            'wifi_password' => $this->mask($profile->wifi_password),
            'wifi_enabled' => (bool) $profile->wifi_enabled,
            'tr069_parameters_json' => $profile->tr069_parameters ? json_encode($profile->tr069_parameters, JSON_PRETTY_PRINT) : '',
            'status' => $profile->status,
            'isp' => $profile->isp ? ['id' => $profile->isp->id, 'name' => $profile->isp->name] : null,
            'edit_url' => route('tr069.profiles.edit', $profile),
            'destroy_url' => route('tr069.profiles.destroy', $profile),
        ];
    }
}
