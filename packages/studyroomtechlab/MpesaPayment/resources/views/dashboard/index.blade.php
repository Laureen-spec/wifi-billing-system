@extends('layouts.app')

@section('page-title')
    M-Pesa Payment Dashboard
@endsection

@section('content')
<div style="max-width:1200px;margin:0 auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <div>
            <h1 style="font-size:28px;margin:0;">M-Pesa Payment Dashboard</h1>
            <p style="margin:6px 0 0;color:#64748b;">
                Monitor collections, ISP wallets, platform fees, and settlements.
            </p>
        </div>

        <div style="display:flex;gap:10px;">
            <a href="{{ route('mpesa-payment.settings.index') }}" class="btn btn-primary">
                M-Pesa Settings
            </a>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:20px;">
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:18px;">
            <p style="margin:0;color:#64748b;">Today Collections</p>
            <h2 style="margin:8px 0 0;">KES {{ number_format($stats['today_collections'] ?? 0, 2) }}</h2>
        </div>

        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:18px;">
            <p style="margin:0;color:#64748b;">Monthly Collections</p>
            <h2 style="margin:8px 0 0;">KES {{ number_format($stats['monthly_collections'] ?? 0, 2) }}</h2>
        </div>

        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:18px;">
            <p style="margin:0;color:#64748b;">Platform Fees</p>
            <h2 style="margin:8px 0 0;">KES {{ number_format($stats['platform_fees'] ?? 0, 2) }}</h2>
        </div>

        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:18px;">
            <p style="margin:0;color:#64748b;">ISP Wallet Balance</p>
            <h2 style="margin:8px 0 0;">KES {{ number_format($stats['isp_wallet_balance'] ?? 0, 2) }}</h2>
        </div>

        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:18px;">
            <p style="margin:0;color:#64748b;">Total Collections</p>
            <h2 style="margin:8px 0 0;">KES {{ number_format($stats['total_collections'] ?? 0, 2) }}</h2>
        </div>

        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:18px;">
            <p style="margin:0;color:#64748b;">Pending Transactions</p>
            <h2 style="margin:8px 0 0;">{{ number_format($stats['pending_transactions'] ?? 0) }}</h2>
        </div>

        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:18px;">
            <p style="margin:0;color:#64748b;">Failed Transactions</p>
            <h2 style="margin:8px 0 0;">{{ number_format($stats['failed_transactions'] ?? 0) }}</h2>
        </div>

        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:18px;">
            <p style="margin:0;color:#64748b;">Pending Settlements</p>
            <h2 style="margin:8px 0 0;">KES {{ number_format($stats['pending_settlements'] ?? 0, 2) }}</h2>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:18px;">
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;">
            <h3 style="margin-top:0;">Recent M-Pesa Transactions</h3>

            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="text-align:left;color:#64748b;border-bottom:1px solid #e5e7eb;">
                            <th style="padding:10px;">Phone</th>
                            <th style="padding:10px;">Amount</th>
                            <th style="padding:10px;">Receipt</th>
                            <th style="padding:10px;">Status</th>
                            <th style="padding:10px;">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransactions as $transaction)
                            <tr style="border-bottom:1px solid #f1f5f9;">
                                <td style="padding:10px;">{{ $transaction->phone ?? '-' }}</td>
                                <td style="padding:10px;">KES {{ number_format((float) $transaction->amount, 2) }}</td>
                                <td style="padding:10px;">{{ $transaction->mpesa_receipt_number ?? '-' }}</td>
                                <td style="padding:10px;">
                                    <span style="padding:5px 9px;border-radius:999px;background:#f1f5f9;">
                                        {{ ucfirst($transaction->status) }}
                                    </span>
                                </td>
                                <td style="padding:10px;">{{ $transaction->created_at?->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="padding:16px;color:#64748b;">
                                    No M-Pesa transactions yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;">
            <h3 style="margin-top:0;">ISP Wallets</h3>

            @forelse($wallets as $wallet)
                <div style="padding:12px 0;border-bottom:1px solid #f1f5f9;">
                    <strong>ISP #{{ $wallet->isp_id }}</strong>
                    <p style="margin:4px 0;color:#64748b;">
                        Balance: KES {{ number_format((float) $wallet->available_balance, 2) }}
                    </p>
                    <small style="color:#64748b;">
                        Earned: KES {{ number_format((float) $wallet->total_earned, 2) }}
                    </small>
                </div>
            @empty
                <p style="color:#64748b;">No ISP wallets yet.</p>
            @endforelse
        </div>
    </div>

    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;margin-top:18px;">
        <h3 style="margin-top:0;">Recent Settlements</h3>

        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="text-align:left;color:#64748b;border-bottom:1px solid #e5e7eb;">
                        <th style="padding:10px;">Settlement No.</th>
                        <th style="padding:10px;">ISP</th>
                        <th style="padding:10px;">Amount</th>
                        <th style="padding:10px;">Net</th>
                        <th style="padding:10px;">Status</th>
                        <th style="padding:10px;">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($settlements as $settlement)
                        <tr style="border-bottom:1px solid #f1f5f9;">
                            <td style="padding:10px;">{{ $settlement->settlement_number }}</td>
                            <td style="padding:10px;">ISP #{{ $settlement->isp_id }}</td>
                            <td style="padding:10px;">KES {{ number_format((float) $settlement->amount, 2) }}</td>
                            <td style="padding:10px;">KES {{ number_format((float) $settlement->net_amount, 2) }}</td>
                            <td style="padding:10px;">{{ ucfirst($settlement->status) }}</td>
                            <td style="padding:10px;">{{ $settlement->created_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:16px;color:#64748b;">
                                No settlements yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection