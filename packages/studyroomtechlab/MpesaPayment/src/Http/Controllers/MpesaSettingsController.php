<?php

namespace StudyRoomTechLab\MpesaPayment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\IspTenantResolver;
use Illuminate\Http\Request;
use StudyRoomTechLab\MpesaPayment\Models\MpesaSetting;

class MpesaSettingsController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizePlatform($request);

        $platformSetting = MpesaSetting::whereNull('isp_id')
            ->where('owner_type', 'platform')
            ->orderByDesc('is_default')
            ->first();

        return view('mpesa-payment::settings.index', [
            'platformSetting' => $platformSetting,
        ]);
    }

    public function savePlatform(Request $request)
    {
        $this->authorizePlatform($request);

        $data = $request->validate([
            'environment' => ['required', 'in:sandbox,live'],
            'business_name' => ['nullable', 'string', 'max:191'],
            'shortcode' => ['required', 'string', 'max:50'],
            'account_reference' => ['nullable', 'string', 'max:100'],
            'consumer_key' => ['nullable', 'string'],
            'consumer_secret' => ['nullable', 'string'],
            'passkey' => ['nullable', 'string'],
            'callback_url' => ['nullable', 'url', 'max:500'],
            'commission_type' => ['required', 'in:percentage,fixed,none'],
            'commission_value' => ['nullable', 'numeric', 'min:0'],
            'allow_isp_direct' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $setting = MpesaSetting::firstOrNew([
            'isp_id' => null,
            'owner_type' => 'platform',
        ]);

        $setting->fill([
            'owner_type' => 'platform',
            'collection_mode' => 'platform',
            'environment' => $data['environment'],
            'business_name' => $data['business_name'] ?? null,
            'shortcode' => $data['shortcode'],
            'account_reference' => $data['account_reference'] ?? 'StudyRoom WiFi',
            'callback_url' => $data['callback_url'] ?? route('mpesa-payment.callback'),
            'commission_type' => $data['commission_type'],
            'commission_value' => $data['commission_value'] ?? 0,
            'is_default' => true,
            'is_active' => $request->boolean('is_active', true),
            'allow_isp_direct' => $request->boolean('allow_isp_direct'),
            'updated_by' => $request->user()?->id,
        ]);

        if (! $setting->exists) {
            $setting->created_by = $request->user()?->id;
        }

        if (! empty($data['consumer_key'])) {
            $setting->consumer_key = encrypt($data['consumer_key']);
        }

        if (! empty($data['consumer_secret'])) {
            $setting->consumer_secret = encrypt($data['consumer_secret']);
        }

        if (! empty($data['passkey'])) {
            $setting->passkey = encrypt($data['passkey']);
        }

        $setting->save();

        return back()->with('success', 'Platform M-Pesa settings saved.');
    }

    private function authorizePlatform(Request $request): void
    {
        abort_unless($request->user(), 403);

        if (class_exists(IspTenantResolver::class)) {
            if (app(IspTenantResolver::class)->isPlatform($request)) {
                return;
            }
        }

        $type = strtolower((string) ($request->user()->type ?? ''));

        abort_unless(in_array($type, [
            'super admin',
            'super_admin',
            'superadmin',
            'company',
            'owner',
        ], true), 403);
    }
}