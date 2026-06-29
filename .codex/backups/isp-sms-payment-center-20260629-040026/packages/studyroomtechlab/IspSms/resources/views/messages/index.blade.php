@extends('layouts.app')
@section('title', 'SMS Messages')
@section('page_heading', 'SMS Messages')
@section('content')
@php $filters = $filters ?? []; @endphp
<style>.sms-page{max-width:1280px;margin:0 auto}.sms-hero{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;margin-bottom:18px}.sms-hero h1{font-size:30px;margin:0}.sms-hero p{color:#64748b;margin:6px 0 0}.sms-btn{display:inline-flex;align-items:center;gap:8px;border:0;border-radius:12px;background:#2563eb;color:#fff;padding:11px 15px;font-weight:800;text-decoration:none}.sms-btn.secondary{background:#eef2ff;color:#1d4ed8}.sms-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;margin-bottom:18px}.sms-stat{background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:16px}.sms-stat span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;font-weight:800}.sms-stat strong{display:block;font-size:26px}.sms-card{background:#fff;border:1px solid #e5e7eb;border-radius:20px;overflow:hidden}.sms-filter{display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:10px;padding:16px;border-bottom:1px solid #edf2f7}.sms-filter input,.sms-filter select{border:1px solid #d8dee9;border-radius:12px;padding:10px 12px}.sms-table{width:100%;border-collapse:collapse}.sms-table th{background:#f8fafc;color:#64748b;font-size:12px;text-transform:uppercase;text-align:left;padding:13px}.sms-table td{border-top:1px solid #edf2f7;padding:13px;vertical-align:top}.sms-badge{display:inline-flex;border-radius:999px;padding:4px 9px;font-size:12px;font-weight:800;background:#f1f5f9;color:#475569}.sms-badge.sent{background:#dcfce7;color:#166534}.sms-badge.failed{background:#fee2e2;color:#991b1b}.sms-badge.queued{background:#fef3c7;color:#92400e}.sms-muted{color:#64748b;font-size:13px}.sms-empty{padding:35px;text-align:center;color:#64748b}.sms-pagination{padding:14px;border-top:1px solid #edf2f7}@media(max-width:1000px){.sms-grid{grid-template-columns:repeat(2,1fr)}.sms-filter{grid-template-columns:1fr}.sms-table{display:block;overflow:auto}.sms-hero{display:block}}</style>
<div class="sms-page">
    <div class="sms-hero">
        <div>
            <h1>SMS Messages</h1>
            <p>Send and monitor ISP customer SMS logs. This is now a separate add-on from WiFi Billing.</p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a class="sms-btn secondary" href="{{ route('isp.sms.settings') }}">SMS Settings</a>
            <a class="sms-btn secondary" href="{{ route('isp.sms.templates.index') }}">Templates</a>
            <a class="sms-btn" href="{{ route('isp.sms.create') }}">Send SMS</a>
        </div>
    </div>

    <div class="sms-grid">
        <div class="sms-stat"><span>Total</span><strong>{{ $stats['total'] ?? 0 }}</strong></div>
        <div class="sms-stat"><span>Queued</span><strong>{{ $stats['queued'] ?? 0 }}</strong></div>
        <div class="sms-stat"><span>Sent</span><strong>{{ $stats['sent'] ?? 0 }}</strong></div>
        <div class="sms-stat"><span>Delivered</span><strong>{{ $stats['delivered'] ?? 0 }}</strong></div>
        <div class="sms-stat"><span>Failed</span><strong>{{ $stats['failed'] ?? 0 }}</strong></div>
    </div>

    <div class="sms-card">
        <form class="sms-filter" method="GET" action="{{ route('isp.sms.index') }}">
            <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search phone, message, receipt...">
            <select name="status"><option value="">All Status</option>@foreach(['queued','sent','delivered','failed'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>@endforeach</select>
            <select name="sending_mode"><option value="">All Modes</option>@foreach(['platform','own'] as $mode)<option value="{{ $mode }}" @selected(($filters['sending_mode'] ?? '') === $mode)>{{ ucfirst($mode) }}</option>@endforeach</select>
            <select name="direction"><option value="">All Direction</option>@foreach(['outbound','inbound'] as $direction)<option value="{{ $direction }}" @selected(($filters['direction'] ?? '') === $direction)>{{ ucfirst($direction) }}</option>@endforeach</select>
            <button class="sms-btn secondary" type="submit">Filter</button>
        </form>

        <table class="sms-table">
            <thead><tr><th>Recipient</th><th>Mode</th><th>Message</th><th>Status</th><th>Sent At</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($messages as $message)
                <tr>
                    <td><strong>{{ $message->customer?->name ?? $message->recipientUser?->name ?? 'Custom Phone' }}</strong><br><span class="sms-muted">{{ $message->phone }}</span></td>
                    <td>{{ ucfirst($message->sending_mode ?? 'platform') }}<br><span class="sms-muted">{{ $message->provider ?? '-' }}</span></td>
                    <td>{{ \Illuminate\Support\Str::limit($message->message, 90) }}</td>
                    <td><span class="sms-badge {{ $message->status }}">{{ ucfirst($message->status) }}</span><br><span class="sms-muted">{{ $message->result_message }}</span></td>
                    <td>{{ $message->sent_at?->format('Y-m-d H:i') ?? '-' }}</td>
                    <td><a class="sms-btn secondary" href="{{ route('isp.sms.show', $message) }}">View</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="sms-empty">No SMS messages yet.</td></tr>
            @endforelse
            </tbody>
        </table>
        @if(method_exists($messages, 'links'))<div class="sms-pagination">{{ $messages->links() }}</div>@endif
    </div>
</div>
@endsection
