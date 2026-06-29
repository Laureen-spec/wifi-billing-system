<?php

namespace StudyRoomTechLab\MpesaPayment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\IspTenantResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use StudyRoomTechLab\MpesaPayment\Models\MpesaSetting;

class MpesaSettingsController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizePlatform($request);

        $platformSetting = $this->platformSetting();

        return view('mpesa-payment::settings.index', [
            'platformSetting' => $platformSetting,
        ]);
    }

    public function admin(Request $request)
    {
        $isp = $this->resolveIsp($request);
        $setting = $this->ispSetting((int) $isp->id);
        $platformSetting = $this->platformSetting();

        return Inertia::render('mpesa-payment/admin-settings', [
            'pageTitle' => 'Payment Addon',
            'subtitle' => 'Choose whether this ISP uses the Super Admin system M-Pesa gateway or its own M-Pesa Daraja credentials.',
            'setting' => $setting?->toAdminPayload(),
            'platformSetting' => $platformSetting?->toPlatformPayload(),
            'isp' => [
                'id' => $isp->id,
                'name' => $isp->name,
                'email' => $isp->email,
                'phone' => $isp->phone,
            ],
            'availableGateways' => [
                ['value' => 'mpesa', 'label' => 'M-Pesa Daraja'],
            ],
            'routes' => [
                'save' => route('mpesa-payment.settings.admin.save'),
                'dashboard' => route('dashboard'),
            ],
        ]);
    }

    public function savePlatform(Request $request)
    {
        $this->authorizePlatform($request);

        $data = $request->validate([
            'active_gateway' => ['nullable', 'in:mpesa'],
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
            'documentation_url' => ['nullable', 'url', 'max:500'],
        ]);

        $setting = MpesaSetting::firstOrNew([
            'isp_id' => null,
            'owner_type' => 'platform',
        ]);

        $setting->fill([
            'owner_type' => 'platform',
            'collection_mode' => 'platform',
            'active_gateway' => $data['active_gateway'] ?? 'mpesa',
            'environment' => $data['environment'],
            'business_name' => $data['business_name'] ?? null,
            'shortcode' => $data['shortcode'],
            'account_reference' => $data['account_reference'] ?? 'StudyRoom WiFi',
            'callback_url' => $data['callback_url'] ?? route('mpesa-payment.callback'),
            'commission_type' => $data['commission_type'],
            'commission_value' => $data['commission_value'] ?? 0,
            'system_payment_channel' => null,
            'system_till_number' => null,
            'system_paybill_number' => null,
            'system_account_number' => null,
            'system_phone_number' => null,
            'documentation_url' => $data['documentation_url'] ?? null,
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

    public function saveAdmin(Request $request)
    {
        $isp = $this->resolveIsp($request);

        $data = $request->validate([
            'payment_mode' => ['required', 'in:system,own'],
            'gateway' => ['required', 'in:mpesa'],
            'is_active' => ['nullable', 'boolean'],

            'system_payment_channel' => ['required_if:payment_mode,system', 'nullable', 'in:till,phone,paybill'],
            'system_till_number' => ['required_if:system_payment_channel,till', 'nullable', 'string', 'max:50'],
            'system_paybill_number' => ['required_if:system_payment_channel,paybill', 'nullable', 'string', 'max:50'],
            'system_account_number' => ['required_if:system_payment_channel,paybill', 'nullable', 'string', 'max:100'],
            'system_phone_number' => ['required_if:system_payment_channel,phone', 'nullable', 'string', 'max:30'],

            'environment' => ['required_if:payment_mode,own', 'nullable', 'in:sandbox,live'],
            'business_name' => ['nullable', 'string', 'max:191'],
            'shortcode' => ['required_if:payment_mode,own', 'nullable', 'string', 'max:50'],
            'account_reference' => ['nullable', 'string', 'max:100'],
            'consumer_key' => ['nullable', 'string'],
            'consumer_secret' => ['nullable', 'string'],
            'passkey' => ['nullable', 'string'],
            'callback_url' => ['nullable', 'url', 'max:500'],
            'documentation_url' => ['nullable', 'url', 'max:500'],
        ]);

        $setting = MpesaSetting::firstOrNew([
            'isp_id' => $isp->id,
            'owner_type' => 'isp',
        ]);

        $isOwnGateway = $data['payment_mode'] === 'own';

        $setting->fill([
            'isp_id' => $isp->id,
            'owner_type' => 'isp',
            'collection_mode' => $isOwnGateway ? 'isp_direct' : 'platform',
            'active_gateway' => $data['gateway'],
            'environment' => $data['environment'] ?? 'sandbox',
            'business_name' => $data['business_name'] ?? $isp->name,
            'shortcode' => $isOwnGateway ? ($data['shortcode'] ?? null) : null,
            'account_reference' => $isOwnGateway
                ? ($data['account_reference'] ?: $isp->name)
                : $this->systemAccountReference($isp->name, $data),
            'callback_url' => $isOwnGateway ? ($data['callback_url'] ?? route('mpesa-payment.callback')) : null,
            'commission_type' => 'none',
            'commission_value' => 0,
            'system_payment_channel' => $isOwnGateway ? null : ($data['system_payment_channel'] ?? 'till'),
            'system_till_number' => $isOwnGateway ? null : ($data['system_till_number'] ?? null),
            'system_paybill_number' => $isOwnGateway ? null : ($data['system_paybill_number'] ?? null),
            'system_account_number' => $isOwnGateway ? null : ($data['system_account_number'] ?? null),
            'system_phone_number' => $isOwnGateway ? null : ($data['system_phone_number'] ?? null),
            'documentation_url' => $data['documentation_url'] ?? null,
            'is_default' => true,
            'is_active' => $request->boolean('is_active', true),
            'allow_isp_direct' => false,
            'updated_by' => $request->user()?->id,
        ]);

        if (! $setting->exists) {
            $setting->created_by = $request->user()?->id;
        }

        if ($isOwnGateway) {
            if (! empty($data['consumer_key'])) {
                $setting->consumer_key = encrypt($data['consumer_key']);
            }

            if (! empty($data['consumer_secret'])) {
                $setting->consumer_secret = encrypt($data['consumer_secret']);
            }

            if (! empty($data['passkey'])) {
                $setting->passkey = encrypt($data['passkey']);
            }
        } else {
            $setting->consumer_key = null;
            $setting->consumer_secret = null;
            $setting->passkey = null;
        }

        $setting->save();

        return back()->with('success', 'Payment addon settings saved.');
    }

    private function platformSetting(): ?MpesaSetting
    {
        return MpesaSetting::whereNull('isp_id')
            ->where('owner_type', 'platform')
            ->orderByDesc('is_default')
            ->first();
    }

    private function ispSetting(int $ispId): ?MpesaSetting
    {
        return MpesaSetting::where('isp_id', $ispId)
            ->where('owner_type', 'isp')
            ->orderByDesc('updated_at')
            ->first();
    }

    private function resolveIsp(Request $request)
    {
        abort_unless($request->user(), 403);

        if (class_exists(IspTenantResolver::class)) {
            return app(IspTenantResolver::class)->resolve($request);
        }

        abort_unless($request->user()?->isp, 403, 'No ISP is assigned to this account.');

        return $request->user()->isp;
    }

    private function systemAccountReference(string $ispName, array $data): string
    {
        $channel = $data['system_payment_channel'] ?? 'till';

        return match ($channel) {
            'paybill' => trim(($data['system_paybill_number'] ?? '') . ' ' . ($data['system_account_number'] ?? '')) ?: $ispName,
            'phone' => $data['system_phone_number'] ?? $ispName,
            default => $data['system_till_number'] ?? $ispName,
        };
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
