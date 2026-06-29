@extends('layouts.app')

@section('page-title')
    ISP Settlements
@endsection

@section('content')
<div style="max-width:1200px;margin:0 auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <div>
            <h1 style="font-size:28px;margin:0;">ISP Settlements</h1>
            <p style="margin:6px 0 0;color:#64748b;">Approve and record manual ISP payouts from M-Pesa collections.</p>
        </div>
        <div style="display:flex;gap:10px;">
            <a href="{{ route('mpesa-payment.dashboard') }}" class="btn btn-secondary">Dashboard</a>
            <a href="{{ route('mpesa-payment.wallets.index') }}" class="btn btn-primary">Wallets</a>
        </div>
    </div>

    @if(session('success'))
        <div style="background:#ecfdf5;border:1px solid #bbf7d0;color:#166534;padding:12px;border-radius:12px;margin-bottom:16px;">{{ session('success') }}</div>
    @endif

    <div style="display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px;margin-bottom:18px;">
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:16px;"><p style="margin:0;color:#64748b;">Pending</p><h3 style="margin:8px 0 0;">KES {{ number_format((float) ($stats['pending'] ?? 0), 2) }}</h3></div>
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:16px;"><p style="margin:0;color:#64748b;">Approved</p><h3 style="margin:8px 0 0;">KES {{ number_format((float) ($stats['approved'] ?? 0), 2) }}</h3></div>
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:16px;"><p style="margin:0;color:#64748b;">Processing</p><h3 style="margin:8px 0 0;">KES {{ number_format((float) ($stats['processing'] ?? 0), 2) }}</h3></div>
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:16px;"><p style="margin:0;color:#64748b;">Paid</p><h3 style="margin:8px 0 0;">KES {{ number_format((float) ($stats['paid'] ?? 0), 2) }}</h3></div>
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:16px;"><p style="margin:0;color:#64748b;">Failed Count</p><h3 style="margin:8px 0 0;">{{ number_format((int) ($stats['failed_count'] ?? 0)) }}</h3></div>
    </div>

    <form method="GET" style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:16px;margin-bottom:18px;">
        <div style="display:grid;grid-template-columns:2fr 1fr auto;gap:12px;align-items:end;">
            <div>
                <label>Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Settlement no., payout phone, receipt..." style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;">
            </div>
            <div>
                <label>Status</label>
                <select name="status" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;">
                    <option value="">All</option>
                    @foreach(['pending','approved','processing','paid','failed','cancelled'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </form>

    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="text-align:left;color:#64748b;border-bottom:1px solid #e5e7eb;">
                        <th style="padding:10px;">Settlement No.</th>
                        <th style="padding:10px;">ISP</th>
                        <th style="padding:10px;">Amount</th>
                        <th style="padding:10px;">Fee</th>
                        <th style="padding:10px;">Net</th>
                        <th style="padding:10px;">Payout</th>
                        <th style="padding:10px;">Status</th>
                        <th style="padding:10px;">Date</th>
                        <th style="padding:10px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($settlements as $settlement)
                        <tr style="border-bottom:1px solid #f1f5f9;">
                            <td style="padding:10px;">{{ $settlement->settlement_number }}</td>
                            <td style="padding:10px;">ISP #{{ $settlement->isp_id }}</td>
                            <td style="padding:10px;">KES {{ number_format((float) $settlement->amount, 2) }}</td>
                            <td style="padding:10px;">KES {{ number_format((float) $settlement->fee, 2) }}</td>
                            <td style="padding:10px;">KES {{ number_format((float) $settlement->net_amount, 2) }}</td>
                            <td style="padding:10px;">{{ $settlement->payout_phone ?: ($settlement->payout_shortcode ?: '-') }}</td>
                            <td style="padding:10px;"><span style="padding:5px 9px;border-radius:999px;background:#f1f5f9;">{{ ucfirst($settlement->status) }}</span></td>
                            <td style="padding:10px;">{{ $settlement->created_at?->format('Y-m-d H:i') }}</td>
                            <td style="padding:10px;"><a href="{{ route('mpesa-payment.settlements.show', $settlement) }}" class="btn btn-sm btn-secondary">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" style="padding:16px;color:#64748b;">No settlements yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:16px;">{{ $settlements->links() }}</div>
    </div>
</div>
@endsection
