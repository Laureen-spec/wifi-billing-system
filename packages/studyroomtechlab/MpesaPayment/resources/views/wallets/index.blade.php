@extends('layouts.app')

@section('page-title')
    ISP Wallets
@endsection

@section('content')
<div style="max-width:1200px;margin:0 auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <div>
            <h1 style="font-size:28px;margin:0;">ISP Wallets</h1>
            <p style="margin:6px 0 0;color:#64748b;">View ISP balances, earnings, pending balances, and payout status.</p>
        </div>
        <div style="display:flex;gap:10px;">
            <a href="{{ route('mpesa-payment.dashboard') }}" class="btn btn-secondary">Dashboard</a>
            <a href="{{ route('mpesa-payment.transactions.index') }}" class="btn btn-primary">Transactions</a>
        </div>
    </div>

    @if(session('success'))
        <div style="background:#ecfdf5;border:1px solid #bbf7d0;color:#166534;padding:12px;border-radius:12px;margin-bottom:16px;">{{ session('success') }}</div>
    @endif

    <div style="display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px;margin-bottom:18px;">
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:16px;"><p style="margin:0;color:#64748b;">Available</p><h3 style="margin:8px 0 0;">KES {{ number_format((float) ($stats['available_balance'] ?? 0), 2) }}</h3></div>
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:16px;"><p style="margin:0;color:#64748b;">Pending</p><h3 style="margin:8px 0 0;">KES {{ number_format((float) ($stats['pending_balance'] ?? 0), 2) }}</h3></div>
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:16px;"><p style="margin:0;color:#64748b;">Total Earned</p><h3 style="margin:8px 0 0;">KES {{ number_format((float) ($stats['total_earned'] ?? 0), 2) }}</h3></div>
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:16px;"><p style="margin:0;color:#64748b;">Paid Out</p><h3 style="margin:8px 0 0;">KES {{ number_format((float) ($stats['total_paid_out'] ?? 0), 2) }}</h3></div>
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:16px;"><p style="margin:0;color:#64748b;">Wallets</p><h3 style="margin:8px 0 0;">{{ number_format((int) ($stats['wallet_count'] ?? 0)) }}</h3></div>
    </div>

    <form method="GET" style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:16px;margin-bottom:18px;">
        <div style="display:grid;grid-template-columns:1fr auto;gap:12px;align-items:end;">
            <div>
                <label>Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="ISP ID, payout phone, payout name..." style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;">
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </form>

    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="text-align:left;color:#64748b;border-bottom:1px solid #e5e7eb;">
                        <th style="padding:10px;">Wallet ID</th>
                        <th style="padding:10px;">ISP</th>
                        <th style="padding:10px;">Available</th>
                        <th style="padding:10px;">Pending</th>
                        <th style="padding:10px;">Total Earned</th>
                        <th style="padding:10px;">Paid Out</th>
                        <th style="padding:10px;">Payout Phone</th>
                        <th style="padding:10px;">Auto Settlement</th>
                        <th style="padding:10px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($wallets as $wallet)
                        <tr style="border-bottom:1px solid #f1f5f9;">
                            <td style="padding:10px;">#{{ $wallet->id }}</td>
                            <td style="padding:10px;">ISP #{{ $wallet->isp_id }}</td>
                            <td style="padding:10px;">KES {{ number_format((float) $wallet->available_balance, 2) }}</td>
                            <td style="padding:10px;">KES {{ number_format((float) $wallet->pending_balance, 2) }}</td>
                            <td style="padding:10px;">KES {{ number_format((float) $wallet->total_earned, 2) }}</td>
                            <td style="padding:10px;">KES {{ number_format((float) $wallet->total_paid_out, 2) }}</td>
                            <td style="padding:10px;">{{ $wallet->payout_phone ?? '-' }}</td>
                            <td style="padding:10px;">{{ $wallet->auto_settlement_enabled ? 'Enabled' : 'Manual' }}</td>
                            <td style="padding:10px;"><a href="{{ route('mpesa-payment.wallets.show', $wallet) }}" class="btn btn-sm btn-secondary">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" style="padding:16px;color:#64748b;">No ISP wallets yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:16px;">{{ $wallets->links() }}</div>
    </div>
</div>
@endsection
