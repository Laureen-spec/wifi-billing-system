@extends('layouts.app')
@section('title', 'SMS Settings')
@section('page_heading', 'SMS Settings')
@section('content')
<style>.sms-page{max-width:980px;margin:0 auto}.sms-card{background:#fff;border:1px solid #e5e7eb;border-radius:20px;padding:22px;margin-bottom:18px}.sms-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}.sms-field{margin-bottom:14px}.sms-field label{display:block;font-weight:800;margin-bottom:6px}.sms-field input,.sms-field select{width:100%;border:1px solid #d8dee9;border-radius:12px;padding:11px 12px}.sms-btn{display:inline-flex;border:0;border-radius:12px;background:#2563eb;color:#fff;padding:11px 15px;font-weight:800;text-decoration:none}.sms-btn.secondary{background:#eef2ff;color:#1d4ed8}.sms-note{background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;border-radius:14px;padding:12px;margin-bottom:14px}@media(max-width:800px){.sms-row{grid-template-columns:1fr}}</style>
<div class="sms-page">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:15px"><div><h1>SMS Settings</h1><p style="color:#64748b;margin:0">Choose platform SMS or ISP-owned SMS gateway.</p></div><a class="sms-btn secondary" href="{{ route('isp.sms.index') }}">Messages</a></div>
    <div class="sms-note">Local safe mode uses <strong>ISP_SMS_DRY_RUN=true</strong>, so messages move from queued to sent without charging or calling a gateway.</div>
    <form class="sms-card" method="POST" action="{{ route('isp.sms.settings.save') }}">
        @csrf
        <div class="sms-row">
            <div class="sms-field"><label>Scope</label><select name="scope"><option value="isp">ISP Setting</option>@if($isPlatform)<option value="platform">Platform Setting</option>@endif</select></div>
            <div class="sms-field"><label>Mode</label><select name="mode"><option value="platform" @selected(($setting?->mode ?? 'platform') === 'platform')>Use Platform Gateway</option><option value="own" @selected(($setting?->mode ?? '') === 'own')>Use Own Gateway</option></select></div>
        </div>
        <div class="sms-row">
            <div class="sms-field"><label>Provider</label><select name="provider"><option value="platform">Platform</option><option value="custom_http">Custom HTTP</option><option value="africastalking">Africa's Talking</option><option value="twilio">Twilio</option><option value="other">Other</option></select></div>
            <div class="sms-field"><label>Sender ID</label><input name="sender_id" value="{{ old('sender_id', $setting?->sender_id ?? $platformSetting?->sender_id) }}" placeholder="StudyRoom"></div>
        </div>
        <div class="sms-row">
            <div class="sms-field"><label>Username</label><input name="username" value="{{ old('username', $setting?->username) }}"></div>
            <div class="sms-field"><label>Custom HTTP URL</label><input name="callback_url" value="{{ old('callback_url', $setting?->callback_url) }}" placeholder="https://gateway.example/send"></div>
        </div>
        <div class="sms-row">
            <div class="sms-field"><label>API Key</label><input name="api_key" type="password" placeholder="Leave blank to keep existing"></div>
            <div class="sms-field"><label>API Secret</label><input name="api_secret" type="password" placeholder="Leave blank to keep existing"></div>
        </div>
        <label style="display:flex;gap:8px;align-items:center;margin-bottom:14px"><input type="checkbox" name="is_active" value="1" checked> Active</label>
        <button class="sms-btn" type="submit">Save SMS Settings</button>
    </form>
</div>
@endsection
