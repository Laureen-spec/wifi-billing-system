@extends('layouts.app')
@section('title', 'SMS Message')
@section('page_heading', 'SMS Message')
@section('content')
<style>.sms-page{max-width:900px;margin:0 auto}.sms-card{background:#fff;border:1px solid #e5e7eb;border-radius:20px;padding:22px}.sms-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.sms-muted{color:#64748b}.sms-badge{display:inline-flex;border-radius:999px;padding:4px 9px;font-weight:800;background:#f1f5f9}.sms-btn{display:inline-flex;border:0;border-radius:12px;background:#eef2ff;color:#1d4ed8;padding:10px 14px;font-weight:800;text-decoration:none}@media(max-width:800px){.sms-grid{grid-template-columns:1fr}}</style>
<div class="sms-page">
    <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:15px"><h1>SMS Message</h1><a class="sms-btn" href="{{ route('isp.sms.index') }}">Back</a></div>
    <div class="sms-card">
        <div class="sms-grid">
            <p><strong>Phone:</strong><br>{{ $message->phone }}</p>
            <p><strong>Status:</strong><br><span class="sms-badge">{{ ucfirst($message->status) }}</span></p>
            <p><strong>Recipient:</strong><br>{{ $message->customer?->name ?? $message->recipientUser?->name ?? '-' }}</p>
            <p><strong>Provider:</strong><br>{{ $message->provider ?? '-' }}</p>
            <p><strong>Mode:</strong><br>{{ ucfirst($message->sending_mode ?? 'platform') }}</p>
            <p><strong>Provider ID:</strong><br>{{ $message->provider_message_id ?? '-' }}</p>
            <p><strong>Sent At:</strong><br>{{ $message->sent_at?->format('Y-m-d H:i:s') ?? '-' }}</p>
            <p><strong>Failed At:</strong><br>{{ $message->failed_at?->format('Y-m-d H:i:s') ?? '-' }}</p>
        </div>
        <hr>
        <p><strong>Message</strong></p>
        <div style="white-space:pre-wrap;background:#f8fafc;border-radius:14px;padding:14px">{{ $message->message }}</div>
        @if($message->result_message)<p class="sms-muted"><strong>Result:</strong> {{ $message->result_message }}</p>@endif
    </div>
</div>
@endsection
