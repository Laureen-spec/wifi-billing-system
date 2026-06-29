@extends('layouts.app')

@section('page-title', 'ISP Leads')
@section('title', 'ISP Leads')

@section('content')
<style>
    .sr-page{max-width:1180px;margin:0 auto}.sr-hero{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;margin-bottom:18px}.sr-hero h1{font-size:30px;margin:0 0 6px}.sr-hero p{margin:0;color:#64748b}.sr-card{background:#fff;border:1px solid #e8edf4;border-radius:22px;box-shadow:0 16px 40px rgba(15,23,42,.07);overflow:hidden}.sr-table{width:100%;border-collapse:separate;border-spacing:0}.sr-table th{font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:#64748b;text-align:left;background:#f8fafc;padding:14px 18px}.sr-table td{padding:16px 18px;border-top:1px solid #edf1f7}.sr-muted{color:#64748b;font-size:13px}.sr-empty{padding:34px;color:#64748b;text-align:center}.sr-pagination{padding:14px 18px;border-top:1px solid #edf1f7}.sr-note{margin-bottom:14px;padding:14px 16px;border-radius:16px;background:#f8fafc;color:#475569;border:1px solid #e2e8f0}
</style>
<div class="sr-page">
    <div class="sr-hero">
        <div>
            <h1>ISP Leads</h1>
            <p>Potential customers collected before they become active WiFi subscribers.</p>
        </div>
        <a class="btn secondary" href="{{ route('isp.customers.index') }}">Customers</a>
    </div>

    @unless($hasLeadTable)
        <div class="sr-note">The <strong>isp_leads</strong> table is not enabled yet. This page is safely staged and will show records when the table is added.</div>
    @endunless

    <div class="sr-card">
        <table class="sr-table">
            <thead><tr><th>Name</th><th>Phone</th><th>Location</th><th>Status</th><th>Created</th></tr></thead>
            <tbody>
                @forelse($leads as $lead)
                    <tr>
                        <td><strong>{{ $lead->name ?? $lead->customer_name ?? '-' }}</strong></td>
                        <td>{{ $lead->phone ?? $lead->mobile ?? '-' }}</td>
                        <td>{{ $lead->location ?? $lead->address ?? '-' }}</td>
                        <td>{{ ucfirst($lead->status ?? 'new') }}</td>
                        <td>{{ $lead->created_at ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="sr-empty">No leads found.</div></td></tr>
                @endforelse
            </tbody>
        </table>
        @if(method_exists($leads, 'links'))<div class="sr-pagination">{{ $leads->links() }}</div>@endif
    </div>
</div>
@endsection
