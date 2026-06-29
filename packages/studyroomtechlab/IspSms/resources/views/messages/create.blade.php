@extends('layouts.app')
@section('title', 'Send SMS')
@section('page_heading', 'Send SMS')
@section('content')
<style>.sms-page{max-width:900px;margin:0 auto}.sms-card{background:#fff;border:1px solid #e5e7eb;border-radius:20px;padding:22px}.sms-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}.sms-field{margin-bottom:14px}.sms-field label{display:block;font-weight:800;margin-bottom:6px}.sms-field input,.sms-field select,.sms-field textarea{width:100%;border:1px solid #d8dee9;border-radius:12px;padding:11px 12px}.sms-btn{display:inline-flex;border:0;border-radius:12px;background:#2563eb;color:#fff;padding:11px 15px;font-weight:800;text-decoration:none}.sms-btn.secondary{background:#eef2ff;color:#1d4ed8}.sms-actions{display:flex;gap:10px}@media(max-width:800px){.sms-row{grid-template-columns:1fr}}</style>
<div class="sms-page">
    <h1>Send SMS</h1>
    <p style="color:#64748b">Choose a recipient. ISP admins can send only to their own WiFi customers.</p>
    <form class="sms-card" method="POST" action="{{ route('isp.sms.store') }}">
        @csrf
        <div class="sms-row">
            <div class="sms-field">
                <label>Recipient Type</label>
                <select name="recipient_type">
                    <option value="customer">WiFi Customer</option>
                    @if($isPlatform)<option value="user">Admin/User</option><option value="phone">Custom Phone</option>@endif
                </select>
            </div>
            <div class="sms-field">
                <label>Customer</label>
                <select name="customer_id">
                    <option value="">Select customer</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }} — {{ $customer->phone ?? $customer->username }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        @if($isPlatform)
            <div class="sms-row">
                <div class="sms-field"><label>Admin/User</label><select name="recipient_user_id"><option value="">Select user</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }} — {{ $user->mobile_no ?? $user->email }}</option>@endforeach</select></div>
                <div class="sms-field"><label>Custom Phone</label><input name="phone" placeholder="2547XXXXXXXX"></div>
            </div>
        @endif
        <div class="sms-field">
            <label>Template</label>
            <select onchange="if(this.value){document.querySelector('[name=message]').value=this.value}">
                <option value="">Select template</option>
                @foreach($templates as $template)<option value="{{ $template->body }}">{{ $template->name }}</option>@endforeach
            </select>
        </div>
        <div class="sms-field"><label>Message</label><textarea name="message" rows="5" required maxlength="1000" placeholder="Write SMS message..."></textarea></div>
        <div class="sms-actions"><button class="sms-btn" type="submit">Send / Queue SMS</button><a class="sms-btn secondary" href="{{ route('isp.sms.index') }}">Cancel</a></div>
    </form>
</div>
@endsection
