@extends('layouts.app')

@section('page-title')
    ISP Wallet Details
@endsection

@section('content')
<div style="max-width:1200px;margin:0 auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <div>
            <h1 style="font-size:28px;margin:0;">ISP Wallet #{{ $wallet->id }}</h1>
            <p style="margin:6px 0 0;color:#64748b;">ISP #{{ $wallet->isp_id }} wallet balance, ledger, payout details, and settlements.</p>
        </div>
        <div style="display:flex;gap:10px;">
            <a href="{{ route('mpesa-payment.wallets.index') }}" class="btn btn-secondary">Back</a>
            <a href="{{ route('mpesa-payment.settlements.index') }}" class="btn btn-primary">Settlements</a>
        </div>
    </div>

    @if(session('success'))
        <div style="background:#ecfdf5;border:1px solid #bbf7d0;color:#166534;padding:12px;border-radius:12px;margin-bottom:16px;">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:12px;border-radius:12px;margin-bottom:16px;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:18px;">
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:16px;"><p style="margin:0;color:#64748b;">Available</p><h3 style="margin:8px 0 0;">KES {{ number_format((float) $wallet->available_balance, 2) }}</h3></div>
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:16px;"><p style="margin:0;color:#64748b;">Pending</p><h3 style="margin:8px 0 0;">KES {{ number_format((float) $wallet->pending_balance, 2) }}</h3></div>
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:16px;"><p style="margin:0;color:#64748b;">Total Earned</p><h3 style="margin:8px 0 0;">KES {{ number_format((float) $wallet->total_earned, 2) }}</h3></div>
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:16px;"><p style="margin:0;color:#64748b;">Paid Out</p><h3 style="margin:8px 0 0;">KES {{ number_format((float) $wallet->total_paid_out, 2) }}</h3></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px;">
        <form method="POST" action="{{ route('mpesa-payment.wallets.payout.update', $wallet) }}" style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;">
            @csrf
            <h3 style="margin-top:0;">Payout Settings</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div><label>Payout Name</label><input name="payout_name" value="{{ old('payout_name', $wallet->payout_name) }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;"></div>
                <div><label>Payout Phone</label><input name="payout_phone" value="{{ old('payout_phone', $wallet->payout_phone) }}" placeholder="2547..." style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;"></div>
                <div><label>Payout Shortcode</label><input name="payout_shortcode" value="{{ old('payout_shortcode', $wallet->payout_shortcode) }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;"></div>
                <div><label>Payout Method</label><select name="payout_method" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;">@foreach(['mpesa','bank','manual'] as $method)<option value="{{ $method }}" @selected(old('payout_method', $wallet->payout_method) === $method)>{{ ucfirst($method) }}</option>@endforeach</select></div>
                <div><label>Minimum Settlement</label><input type="number" step="0.01" name="minimum_settlement_amount" value="{{ old('minimum_settlement_amount', $wallet->minimum_settlement_amount) }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;"></div>
                <div><label>Schedule</label><select name="settlement_schedule" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;">@foreach(['manual','daily','weekly','monthly'] as $schedule)<option value="{{ $schedule }}" @selected(old('settlement_schedule', $wallet->settlement_schedule) === $schedule)>{{ ucfirst($schedule) }}</option>@endforeach</select></div>
            </div>
            <div style="display:flex;gap:18px;margin-top:12px;">
                <label><input type="checkbox" name="auto_settlement_enabled" value="1" @checked(old('auto_settlement_enabled', $wallet->auto_settlement_enabled))> Auto settlement</label>
                <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $wallet->is_active))> Active</label>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top:14px;">Save Payout Settings</button>
        </form>

        <form method="POST" action="{{ route('mpesa-payment.wallets.settlements.store', $wallet) }}" style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;">
            @csrf
            <h3 style="margin-top:0;">Create Settlement</h3>
            <p style="color:#64748b;">This reserves money from available balance for manual payout approval.</p>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div><label>Amount</label><input type="number" step="0.01" name="amount" value="{{ old('amount', (float) $wallet->available_balance) }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;"></div>
                <div><label>Fee</label><input type="number" step="0.01" name="fee" value="{{ old('fee', 0) }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;"></div>
            </div>
            <div style="margin-top:12px;"><label>Notes</label><textarea name="notes" rows="3" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;">{{ old('notes') }}</textarea></div>
            <button type="submit" class="btn btn-primary" style="margin-top:14px;" @disabled((float) $wallet->available_balance <= 0)>Create Settlement</button>
        </form>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;">
            <h3 style="margin-top:0;">Wallet Ledger</h3>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead><tr style="text-align:left;color:#64748b;border-bottom:1px solid #e5e7eb;"><th style="padding:10px;">Type</th><th style="padding:10px;">Amount</th><th style="padding:10px;">Balance</th><th style="padding:10px;">Date</th></tr></thead>
                    <tbody>
                        @forelse($ledger as $row)
                            <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:10px;">{{ ucfirst(str_replace('_', ' ', $row->type)) }}</td><td style="padding:10px;">KES {{ number_format((float) $row->amount, 2) }}</td><td style="padding:10px;">KES {{ number_format((float) $row->balance_after, 2) }}</td><td style="padding:10px;">{{ $row->created_at?->format('Y-m-d H:i') }}</td></tr>
                        @empty
                            <tr><td colspan="4" style="padding:16px;color:#64748b;">No ledger records yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top:16px;">{{ $ledger->links() }}</div>
        </div>

        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;">
            <h3 style="margin-top:0;">Recent Settlements</h3>
            @forelse($settlements as $settlement)
                <div style="display:flex;justify-content:space-between;gap:10px;padding:12px 0;border-bottom:1px solid #f1f5f9;">
                    <div><strong>{{ $settlement->settlement_number }}</strong><p style="margin:4px 0;color:#64748b;">KES {{ number_format((float) $settlement->net_amount, 2) }} · {{ ucfirst($settlement->status) }}</p></div>
                    <a href="{{ route('mpesa-payment.settlements.show', $settlement) }}" class="btn btn-sm btn-secondary">View</a>
                </div>
            @empty
                <p style="color:#64748b;">No settlements yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
