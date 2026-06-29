<?php

namespace StudyRoomTechLab\WifiBilling\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\IspTenantResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use StudyRoomTechLab\WifiBilling\Services\HotspotTemplateService;

class HotspotTemplateSettingsController extends Controller
{
    public function __construct(
        private readonly IspTenantResolver $tenantResolver,
        private readonly HotspotTemplateService $templates
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorizeView($request);
        $isp = $this->tenantResolver->resolve($request);

        return Inertia::render('wifi-billing/settings/index', [
            'isp' => [
                'id' => $isp->id,
                'name' => $isp->name,
            ],
            'hotspotTemplate' => $this->templates->payloadForIsp((int) $isp->id),
        ]);
    }

    public function edit(Request $request): Response
    {
        $this->authorizeView($request);
        $isp = $this->tenantResolver->resolve($request);

        return Inertia::render('wifi-billing/settings/hotspot-template', [
            'isp' => [
                'id' => $isp->id,
                'name' => $isp->name,
            ],
            'setting' => $this->templates->payloadForIsp((int) $isp->id),
            'templateOptions' => $this->templates->templateOptions(),
            'languageOptions' => $this->templates->languageOptions(),
        ]);
    }

    public function update(Request $request)
    {
        $this->authorizeManage($request);
        $isp = $this->tenantResolver->resolve($request);

        $data = $request->validate([
            'template_key' => ['required', Rule::in(['simple', 'modern', 'datalan_free_access', 'custom'])],
            'template_name' => ['required', 'string', 'max:120'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'background' => ['nullable', 'image', 'max:4096'],
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'welcome_text' => ['nullable', 'string', 'max:500'],
            'footer_text' => ['nullable', 'string', 'max:255'],
            'care_phone' => ['nullable', 'string', 'max:50'],
            'redirect_url' => ['nullable', 'url', 'max:500'],
            'language' => ['required', Rule::in(['en', 'sw', 'fr'])],
            'purchase_instructions' => ['nullable', 'array', 'max:8'],
            'purchase_instructions.*' => ['nullable', 'string', 'max:255'],
            'custom_css' => ['nullable', 'string', 'max:5000'],
            'enable_datalan_free_access' => ['nullable', 'boolean'],
            'free_access_duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'free_access_cooldown_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'free_access_package_id' => ['nullable', 'integer', 'min:1'],
            'free_access_speed_limit' => ['nullable', 'string', 'max:120'],
            'free_access_identity_mode' => ['required', Rule::in(['mac', 'phone', 'both'])],
            'free_access_requires_phone' => ['nullable', 'boolean'],
            'free_access_requires_name' => ['nullable', 'boolean'],
            'free_access_button_text' => ['nullable', 'string', 'max:120'],
            'free_access_cooldown_message' => ['nullable', 'string', 'max:255'],
            'free_access_success_message' => ['nullable', 'string', 'max:255'],
        ]);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('wifi-billing/hotspot-templates', 'public');
        }

        if ($request->hasFile('background')) {
            $data['background_path'] = $request->file('background')->store('wifi-billing/hotspot-templates', 'public');
        }

        $data['purchase_instructions'] = array_values(array_filter(
            $data['purchase_instructions'] ?? [],
            fn ($instruction) => trim((string) $instruction) !== ''
        ));

        $data['enable_datalan_free_access'] = $request->boolean('enable_datalan_free_access');
        $data['free_access_requires_phone'] = $request->boolean('free_access_requires_phone');
        $data['free_access_requires_name'] = $request->boolean('free_access_requires_name');
        $data['free_access_duration_minutes'] = (int) ($data['free_access_duration_minutes'] ?? 60);
        $data['free_access_cooldown_hours'] = (int) ($data['free_access_cooldown_hours'] ?? 24);
        $data['free_access_package_id'] = $data['free_access_package_id'] ?? null;

        unset($data['logo'], $data['background']);

        $this->templates->saveForIsp((int) $isp->id, $data);

        return redirect()
            ->route('wifi-billing.settings.hotspot-template.edit')
            ->with('success', 'Hotspot template settings saved.');
    }

    private function authorizeView(Request $request): void
    {
        $user = $request->user();
        abort_unless($user, 403);

        if ($this->isCompanyOwner($user)) {
            return;
        }

        abort_unless(
            $user->can('view-wifi-dashboard')
            || $user->can('manage-wifi-dashboard')
            || $user->can('view-isp-customers')
            || $user->can('manage-isp-customers'),
            403
        );
    }

    private function authorizeManage(Request $request): void
    {
        $user = $request->user();
        abort_unless($user, 403);

        if ($this->isCompanyOwner($user)) {
            return;
        }

        abort_unless(
            $user->can('manage-wifi-dashboard') || $user->can('manage-isp-customers'),
            403
        );
    }

    private function isCompanyOwner(User $user): bool
    {
        if (in_array((string) $user->type, ['company', 'admin', 'isp_admin'], true)) {
            return true;
        }

        return method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['company', 'admin', 'isp_admin']);
    }
}
