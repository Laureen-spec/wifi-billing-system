@extends('layouts.app')

@section('page-title', $page['title'] ?? 'WiFi Billing Module')
@section('title', $page['title'] ?? 'WiFi Billing Module')

@section('content')
<style>
    .sr-module-wrap{max-width:1180px;margin:0 auto}.sr-module-hero{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;margin-bottom:18px}.sr-module-hero h1{font-size:30px;margin:0 0 6px}.sr-module-hero p{margin:0;color:#64748b}.sr-card{background:#fff;border:1px solid #e8edf4;border-radius:22px;box-shadow:0 16px 40px rgba(15,23,42,.07);overflow:hidden}.sr-card-head{display:flex;justify-content:space-between;gap:12px;padding:18px 20px;border-bottom:1px solid #eef2f7}.sr-badge{display:inline-flex;border-radius:999px;background:#eef6ff;color:#1d4ed8;border:1px solid #bfdbfe;padding:6px 10px;font-size:12px;font-weight:800}.sr-table{width:100%;border-collapse:separate;border-spacing:0}.sr-table th{font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:#64748b;text-align:left;background:#f8fafc;padding:14px 18px}.sr-table td{padding:18px;border-top:1px solid #edf1f7;color:#64748b}.sr-note{margin-top:14px;padding:14px 16px;border-radius:16px;background:#f8fafc;color:#475569;border:1px solid #e2e8f0}
</style>
<div class="sr-module-wrap">
    <div class="sr-module-hero">
        <div>
            <h1>{{ $page['title'] ?? 'WiFi Billing Module' }}</h1>
            <p>{{ $page['subtitle'] ?? 'This page is ready for setup.' }}</p>
        </div>
        <span class="sr-badge">{{ $page['status'] ?? 'Ready' }}</span>
    </div>

    <div class="sr-card">
        <div class="sr-card-head">
            <strong>{{ $page['title'] ?? 'Records' }}</strong>
            <span class="sr-badge">Safe placeholder</span>
        </div>
        <table class="sr-table">
            <thead>
                <tr>
                    @foreach(($page['columns'] ?? ['Name','Status','Action']) as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="{{ count($page['columns'] ?? ['Name','Status','Action']) }}">
                        This page has been merged safely. Data storage can be enabled when the related migrations and models are ready.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    @if(!empty($page['note']))
        <div class="sr-note">{{ $page['note'] }}</div>
    @endif
</div>
@endsection
