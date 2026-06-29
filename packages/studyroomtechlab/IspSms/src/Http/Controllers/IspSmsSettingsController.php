<?php

namespace StudyRoomTechLab\IspSms\Http\Controllers;

use App\Http\Controllers\Controller;
use StudyRoomTechLab\IspSms\Models\IspSmsSetting;
use App\Services\IspTenantResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class IspSmsSettingsController extends Controller
{
    public function edit(Request $request)
    {
        $this->authorizeManage($request);

        $isPlatform = $this->isPlatform($request);
        $isp = $isPlatform ? null : $this->resolveIsp($request);

        $setting = null;
        $platformSetting = null;

        if (Schema::hasTable('isp_sms_settings')) {
            if ($isp) {
                $setting = IspSmsSetting::where('scope', 'isp')
                    ->where('isp_id', $isp->id)
                    ->first();
            }

            $platformSetting = IspSmsSetting::where('scope', 'platform')
                ->whereNull('isp_id')
                ->first();
        }

        return $this->viewOrPlaceholder('settings.edit', [
            'setting' => $setting,
            'platformSetting' => $platformSetting,
            'isPlatform' => $isPlatform,
        ], [
            'title' => 'SMS Settings',
            'subtitle' => 'Choose whether this ISP uses platform SMS or its own SMS gateway.',
            'status' => 'Ready for setup',
            'columns' => ['Mode', 'Provider', 'Sender ID', 'Status'],
            'note' => 'SMS settings are local to WiFi Billing and separate from sample communication modules.',
        ]);
    }

    public function update(Request $request)
    {
        $this->authorizeManage($request);

        abort_unless(Schema::hasTable('isp_sms_settings'), 500, 'SMS settings table is not migrated yet.');

        $isPlatform = $this->isPlatform($request);

        $data = $request->validate([
            'scope' => ['nullable', Rule::in(['isp', 'platform'])],
            'mode' => ['required', Rule::in(['platform', 'own'])],
            'provider' => ['nullable', Rule::in(['platform', 'africastalking', 'twilio', 'custom_http', 'other'])],
            'sender_id' => ['nullable', 'string', 'max:100'],
            'api_key' => ['nullable', 'string'],
            'api_secret' => ['nullable', 'string'],
            'username' => ['nullable', 'string', 'max:255'],
            'callback_url' => ['nullable', 'url', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $scope = $data['scope'] ?? 'isp';

        if (! $isPlatform) {
            $scope = 'isp';
        }

        if ($scope === 'platform') {
            abort_unless($isPlatform, 403, 'Only platform users can update platform SMS settings.');
            $ispId = null;
        } else {
            $ispId = $this->resolveIsp($request)->id;
        }

        $provider = $data['provider'] ?? null;

        if ($data['mode'] === 'platform') {
            $provider = 'platform';
        }

        $setting = IspSmsSetting::firstOrNew([
            'scope' => $scope,
            'isp_id' => $ispId,
        ]);

        if (! $setting->exists) {
            $setting->created_by = $request->user()->id;
        }

        $setting->fill([
            'scope' => $scope,
            'isp_id' => $ispId,
            'mode' => $data['mode'],
            'provider' => $provider,
            'sender_id' => $data['sender_id'] ?? null,
            'username' => $data['username'] ?? null,
            'callback_url' => $data['callback_url'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'updated_by' => $request->user()->id,
        ]);

        if (! empty($data['api_key'])) {
            $setting->api_key = $data['api_key'];
        }

        if (! empty($data['api_secret'])) {
            $setting->api_secret = $data['api_secret'];
        }

        $setting->save();

        return redirect()
            ->route('isp.sms.settings')
            ->with('success', 'SMS settings saved.');
    }

    private function resolveIsp(Request $request)
    {
        return app(IspTenantResolver::class)->resolve($request);
    }

    private function isPlatform(Request $request): bool
    {
        return app(IspTenantResolver::class)->isPlatform($request);
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless(
            $this->isPlatform($request)
            || $request->user()->can('manage-wifi-dashboard')
            || $request->user()->can('manage-isp-customers'),
            403
        );
    }

    private function viewOrPlaceholder(string $view, array $data, array $page)
    {
        $packageView = 'isp-sms::' . $view;

        if (view()->exists($packageView)) {
            return view($packageView, $data);
        }

        if (view()->exists($view)) {
            return view($view, $data);
        }

        return view('isp-modules.placeholder', [
            'page' => $page,
        ]);
    }
}