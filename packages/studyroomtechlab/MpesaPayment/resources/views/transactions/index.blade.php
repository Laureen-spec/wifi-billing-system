@extends('layouts.app')

@section('page-title')
    M-Pesa Transactions
@endsection

@section('content')
<div style="max-width:1200px;margin:0 auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <div>
            <h1 style="font-size:28px;margin:0;">M-Pesa Transactions</h1>
            <p style="margin:6px 0 0;color:#64748b;">View STK Push requests, callbacks, receipts, failed payments, and provisioning links.</p>
        </div>
        <div style="display:flex;gap:10px;">
            <a href="{{ route('mpesa-payment.dashboard') }}" class="btn btn-secondary">Dashboard</a>
            <a href="{{ route('mpesa-payment.settings.index') }}" class="btn btn-primary">Settings</a>
        </div>
    </div>

    @if(session('success'))
        <div style="background:#ecfdf5;border:1px solid #bbf7d0;color:#166534;padding:12px;border-radius:12px;margin-bottom:16px;">{{ session('success') }}</div>
    @endif

    <form method="GET" style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:16px;margin-bottom:18px;">
        <div style="display:grid;grid-template-columns:2fr 1fr auto;gap:12px;align-items:end;">
            <div>
                <label>Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Phone, receipt, checkout request..." style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;">
            </div>
            <div>
                <label>Status</label>
                <select name="status" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;">
                    <option value="">All</option>
                    @foreach(['pending','stk_sent','paid','failed','cancelled','expired','reversed'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
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
                        <th style="padding:10px;">ID</th>
                        <th style="padding:10px;">Phone</th>
                        <th style="padding:10px;">Amount</th>
                        <th style="padding:10px;">Receipt</th>
                        <th style="padding:10px;">Mode</th>
                        <th style="padding:10px;">Status</th>
                        <th style="padding:10px;">Provisioning</th>
                        <th style="padding:10px;">Date</th>
                        <th style="padding:10px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr style="border-bottom:1px solid #f1f5f9;">
                            <td style="padding:10px;">#{{ $transaction->id }}</td>
                            <td style="padding:10px;">{{ $transaction->phone ?? '-' }}</td>
                            <td style="padding:10px;">KES {{ number_format((float) $transaction->amount, 2) }}</td>
                            <td style="padding:10px;">{{ $transaction->mpesa_receipt_number ?? '-' }}</td>
                            <td style="padding:10px;">{{ ucfirst(str_replace('_', ' ', $transaction->collection_mode ?? 'platform')) }}</td>
                            <td style="padding:10px;"><span style="padding:5px 9px;border-radius:999px;background:#f1f5f9;">{{ ucfirst(str_replace('_', ' ', $transaction->status)) }}</span></td>
                            <td style="padding:10px;">{{ $transaction->provisioning_triggered ? 'Triggered' : '-' }}</td>
                            <td style="padding:10px;">{{ $transaction->created_at?->format('Y-m-d H:i') }}</td>
                            <td style="padding:10px;"><a href="{{ route('mpesa-payment.transactions.show', $transaction) }}" class="btn btn-sm btn-secondary">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" style="padding:16px;color:#64748b;">No M-Pesa transactions found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:16px;">{{ $transactions->links() }}</div>
    </div>
</div>
@endsection
