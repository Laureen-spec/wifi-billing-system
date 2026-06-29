@extends('layouts.app')
@section('title', 'SMS Templates')
@section('page_heading', 'SMS Templates')
@section('content')
<style>.sms-page{max-width:1100px;margin:0 auto}.sms-card{background:#fff;border:1px solid #e5e7eb;border-radius:20px;padding:20px;margin-bottom:18px}.sms-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}.sms-field{margin-bottom:14px}.sms-field label{display:block;font-weight:800;margin-bottom:6px}.sms-field input,.sms-field select,.sms-field textarea{width:100%;border:1px solid #d8dee9;border-radius:12px;padding:11px 12px}.sms-btn{display:inline-flex;border:0;border-radius:12px;background:#2563eb;color:#fff;padding:11px 15px;font-weight:800;text-decoration:none}.sms-table{width:100%;border-collapse:collapse}.sms-table th{background:#f8fafc;color:#64748b;font-size:12px;text-align:left;text-transform:uppercase;padding:12px}.sms-table td{border-top:1px solid #edf2f7;padding:12px}@media(max-width:800px){.sms-row{grid-template-columns:1fr}}</style>
<div class="sms-page">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:15px"><div><h1>SMS Templates</h1><p style="color:#64748b;margin:0">Reusable messages for WiFi customer alerts.</p></div><a class="sms-btn" href="{{ route('isp.sms.index') }}">Messages</a></div>
    <form class="sms-card" method="POST" action="{{ route('isp.sms.templates.store') }}">
        @csrf
        <div class="sms-row">
            <div class="sms-field"><label>Name</label><input name="name" required placeholder="Payment Reminder"></div>
            <div class="sms-field"><label>Key</label><input name="key" placeholder="payment_reminder"></div>
        </div>
        @if($isPlatform)<div class="sms-field"><label>Scope</label><select name="scope"><option value="isp">ISP</option><option value="platform">Platform</option></select></div>@endif
        <div class="sms-field"><label>Body</label><textarea name="body" rows="4" required placeholder="Dear {customer_name}, your WiFi subscription is due..."></textarea></div>
        <label style="display:flex;gap:8px;align-items:center;margin-bottom:14px"><input type="checkbox" name="enabled" value="1" checked> Enabled</label>
        <button class="sms-btn" type="submit">Save Template</button>
    </form>
    <div class="sms-card" style="padding:0;overflow:hidden">
        <table class="sms-table"><thead><tr><th>Name</th><th>Key</th><th>Scope</th><th>Enabled</th><th>Updated</th></tr></thead><tbody>
        @forelse($templates as $template)<tr><td><strong>{{ $template->name }}</strong><br>{{ \Illuminate\Support\Str::limit($template->body, 80) }}</td><td>{{ $template->key }}</td><td>{{ $template->isp_id ? 'ISP' : 'Platform' }}</td><td>{{ $template->enabled ? 'Yes' : 'No' }}</td><td>{{ $template->updated_at?->format('Y-m-d H:i') }}</td></tr>@empty<tr><td colspan="5" style="text-align:center;color:#64748b;padding:30px">No templates yet.</td></tr>@endforelse
        </tbody></table>
        @if(method_exists($templates, 'links'))<div style="padding:14px">{{ $templates->links() }}</div>@endif
    </div>
</div>
@endsection
