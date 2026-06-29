<?php

namespace StudyRoomTechLab\Loyalty\Http\Controllers;

use App\Models\InternetPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use StudyRoomTechLab\Loyalty\Models\LoyaltySetting;

class LoyaltySettingsController extends LoyaltyController
{
    public function edit(Request $request): Response
    {
        $this->authorizeAccess($request, true);

        $isp = $this->resolveIsp($request, $request->integer('isp_id') ?: null);
        $setting = LoyaltySetting::query()->firstOrCreate(
            ['isp_id' => $isp->id],
            LoyaltySetting::defaults((int) $isp->id)
        );

        return Inertia::render('loyalty/settings', [
            'isp' => ['id' => $isp->id, 'name' => $isp->name],
            'isPlatform' => $this->isPlatform($request),
            'isps' => $this->ispOptions($request),
            'setting' => $this->settingPayload($setting),
            'packageOptions' => $this->packageOptions((int) $isp->id),
            'saveUrl' => route('loyalty.settings.save', $this->isPlatform($request) ? ['isp_id' => $isp->id] : []),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeAccess($request, true);

        $data = $request->validate([
            'isp_id' => ['nullable', 'integer', 'exists:isps,id'],
            'enabled' => ['nullable', 'boolean'],
            'default_points_per_payment' => ['required', 'integer', 'min:0', 'max:1000000'],
            'points_per_amount' => ['required', 'integer', 'min:0', 'max:1000000'],
            'amount_step' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'voucher_threshold' => ['required', 'integer', 'min:1', 'max:1000000'],
            'voucher_package_name' => ['nullable', 'string', 'max:255'],
            'voucher_duration_minutes' => ['required', 'integer', 'min:1', 'max:525600'],
            'points_expiry_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'auto_generate_voucher' => ['nullable', 'boolean'],
        ]);

        $ispId = $this->tenantIdForWrite($request);

        LoyaltySetting::query()->updateOrCreate(
            ['isp_id' => $ispId],
            array_merge($data, [
                'isp_id' => $ispId,
                'enabled' => $request->boolean('enabled'),
                'auto_generate_voucher' => $request->boolean('auto_generate_voucher'),
                'updated_by' => $request->user()->id,
            ])
        );

        return redirect()
            ->route('loyalty.settings', $this->isPlatform($request) ? ['isp_id' => $ispId] : [])
            ->with('success', 'Loyalty settings saved.');
    }

    private function settingPayload(LoyaltySetting $setting): array
    {
        return [
            'id' => $setting->id,
            'isp_id' => $setting->isp_id,
            'enabled' => (bool) $setting->enabled,
            'default_points_per_payment' => (int) $setting->default_points_per_payment,
            'points_per_amount' => (int) $setting->points_per_amount,
            'amount_step' => (float) $setting->amount_step,
            'voucher_threshold' => (int) $setting->voucher_threshold,
            'voucher_package_name' => $setting->voucher_package_name,
            'voucher_duration_minutes' => (int) $setting->voucher_duration_minutes,
            'points_expiry_days' => $setting->points_expiry_days,
            'auto_generate_voucher' => (bool) $setting->auto_generate_voucher,
        ];
    }

    private function packageOptions(int $ispId): array
    {
        if (! class_exists(InternetPackage::class) || ! Schema::hasTable('internet_packages')) {
            return [];
        }

        return InternetPackage::query()
            ->where('isp_id', $ispId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (InternetPackage $package): array => [
                'value' => $package->name,
                'label' => $package->name,
            ])
            ->values()
            ->all();
    }
}
