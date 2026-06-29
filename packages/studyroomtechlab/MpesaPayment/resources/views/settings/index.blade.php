@extends('layouts.app')

@section('page-title')
    M-Pesa Payment Settings
@endsection

@section('content')
<div style="max-width: 1100px; margin: 0 auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <div>
            <h1 style="font-size:28px;margin:0;">M-Pesa Payment Settings</h1>
            <p style="margin:6px 0 0;color:#64748b;">
                Manage platform M-Pesa collection, commission, and ISP direct payment options.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div style="background:#ecfdf5;border:1px solid #bbf7d0;color:#166534;padding:12px 14px;border-radius:12px;margin-bottom:16px;">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:12px 14px;border-radius:12px;margin-bottom:16px;">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:12px 14px;border-radius:12px;margin-bottom:16px;">
            <strong>Please fix:</strong>
            <ul style="margin:8px 0 0;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('mpesa-payment.settings.platform.save') }}">
        @csrf

        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;margin-bottom:18px;">
            <h3 style="margin-top:0;">Platform Collection Account</h3>
            <p style="color:#64748b;margin-top:-6px;">
                Customer payments will go to Super Admin M-Pesa first, then ISP wallet is credited.
            </p>

            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;">
                <div>
                    <label>Environment</label>
                    <select name="environment" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;">
                        <option value="sandbox" @selected(old('environment', $platformSetting->environment ?? 'sandbox') === 'sandbox')>Sandbox</option>
                        <option value="live" @selected(old('environment', $platformSetting->environment ?? '') === 'live')>Live</option>
                    </select>
                </div>

                <div>
                    <label>Business Name</label>
                    <input type="text" name="business_name" value="{{ old('business_name', $platformSetting->business_name ?? '') }}"
                           style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;">
                </div>

                <div>
                    <label>Shortcode / Till / Paybill</label>
                    <input type="text" name="shortcode" value="{{ old('shortcode', $platformSetting->shortcode ?? '') }}"
                           required style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;">
                </div>

                <div>
                    <label>Account Reference</label>
                    <input type="text" name="account_reference" value="{{ old('account_reference', $platformSetting->account_reference ?? 'StudyRoom WiFi') }}"
                           style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;">
                </div>

                <div>
                    <label>Consumer Key</label>
                    <input type="password" name="consumer_key" placeholder="{{ $platformSetting?->consumer_key ? 'Saved - leave blank to keep' : 'Enter consumer key' }}"
                           style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;">
                </div>

                <div>
                    <label>Consumer Secret</label>
                    <input type="password" name="consumer_secret" placeholder="{{ $platformSetting?->consumer_secret ? 'Saved - leave blank to keep' : 'Enter consumer secret' }}"
                           style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;">
                </div>

                <div>
                    <label>Passkey</label>
                    <input type="password" name="passkey" placeholder="{{ $platformSetting?->passkey ? 'Saved - leave blank to keep' : 'Enter passkey' }}"
                           style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;">
                </div>

                <div>
                    <label>Callback URL</label>
                    <input type="url" name="callback_url"
                           value="{{ old('callback_url', $platformSetting->callback_url ?? route('mpesa-payment.callback')) }}"
                           style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;">
                </div>
            </div>
        </div>

        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;margin-bottom:18px;">
            <h3 style="margin-top:0;">Commission & ISP Options</h3>

            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;">
                <div>
                    <label>Commission Type</label>
                    <select name="commission_type" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;">
                        <option value="percentage" @selected(old('commission_type', $platformSetting->commission_type ?? 'percentage') === 'percentage')>Percentage</option>
                        <option value="fixed" @selected(old('commission_type', $platformSetting->commission_type ?? '') === 'fixed')>Fixed Amount</option>
                        <option value="none" @selected(old('commission_type', $platformSetting->commission_type ?? '') === 'none')>No Commission</option>
                    </select>
                </div>

                <div>
                    <label>Commission Value</label>
                    <input type="number" step="0.01" min="0" name="commission_value"
                           value="{{ old('commission_value', $platformSetting->commission_value ?? 20) }}"
                           style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;">
                </div>

                <div>
                    <label style="display:flex;gap:8px;align-items:center;margin-top:12px;">
                        <input type="checkbox" name="allow_isp_direct" value="1"
                               @checked(old('allow_isp_direct', $platformSetting->allow_isp_direct ?? false))>
                        Allow ISP/Admin own M-Pesa API
                    </label>
                </div>

                <div>
                    <label style="display:flex;gap:8px;align-items:center;margin-top:12px;">
                        <input type="checkbox" name="is_active" value="1"
                               @checked(old('is_active', $platformSetting->is_active ?? true))>
                        Platform M-Pesa active
                    </label>
                </div>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:10px;">
            <a href="{{ url('/dashboard') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save M-Pesa Settings</button>
        </div>
    </form>
</div>
@endsection