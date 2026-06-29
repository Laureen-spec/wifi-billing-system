@extends('layouts.app')

@section('page-title')
    Settlement Details
@endsection

@section('content')
<div style="max-width:1100px;margin:0 auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <div>
            <h1 style="font-size:28px;margin:0;">Settlement {{ $settlement->settlement_number }}</h1>
            <p style="margin:6px 0 0;color:#64748b;">Review payout details, approve, mark paid, or return failed funds.</p>
        </div>
        <div style="display:flex;gap:10px;">
            <a href="{{ route('mpesa-payment.settlements.index') }}" class="btn btn-secondary">Back</a>
            @if($settlement->wallet)
                <a href="{{ route('mpesa-payment.wallets.show', $settlement->wallet) }}" class="btn btn-primary">Wallet</a>
            @endif
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

    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;">
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;">
            <h3 style="margin-top:0;">Settlement Info</h3>
            <p><strong>Status:</strong> {{ ucfirst($settlement->status) }}</p>
            <p><strong>ISP:</strong> ISP #{{ $settlement->isp_id }}</p>
            <p><strong>Amount:</strong> KES {{ number_format((float) $settlement->amount, 2) }}</p>
            <p><strong>Fee:</strong> KES {{ number_format((float) $settlement->fee, 2) }}</p>
            <p><strong>Net Amount:</strong> KES {{ number_format((float) $settlement->net_amount, 2) }}</p>
            <p><strong>Currency:</strong> {{ $settlement->currency ?? 'KES' }}</p>
        </div>

        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;">
            <h3 style="margin-top:0;">Payout Details</h3>
            <p><strong>Method:</strong> {{ ucfirst($settlement->payout_method ?? 'mpesa') }}</p>
            <p><strong>Name:</strong> {{ $settlement->payout_name ?? '-' }}</p>
            <p><strong>Phone:</strong> {{ $settlement->payout_phone ?? '-' }}</p>
            <p><strong>Shortcode:</strong> {{ $settlement->payout_shortcode ?? '-' }}</p>
            <p><strong>M-Pesa Receipt:</strong> {{ $settlement->mpesa_receipt_number ?? '-' }}</p>
            <p><strong>Reference:</strong> {{ $settlement->transaction_reference ?? '-' }}</p>
        </div>

        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;">
            <h3 style="margin-top:0;">Timeline</h3>
            <p><strong>Requested:</strong> {{ $settlement->requested_at?->format('Y-m-d H:i:s') ?? '-' }}</p>
            <p><strong>Approved:</strong> {{ $settlement->approved_at?->format('Y-m-d H:i:s') ?? '-' }}</p>
            <p><strong>Processed:</strong> {{ $settlement->processed_at?->format('Y-m-d H:i:s') ?? '-' }}</p>
            <p><strong>Paid:</strong> {{ $settlement->paid_at?->format('Y-m-d H:i:s') ?? '-' }}</p>
            <p><strong>Failed:</strong> {{ $settlement->failed_at?->format('Y-m-d H:i:s') ?? '-' }}</p>
        </div>

        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;">
            <h3 style="margin-top:0;">Notes</h3>
            <p><strong>Notes:</strong> {{ $settlement->notes ?? '-' }}</p>
            <p><strong>Failure Reason:</strong> {{ $settlement->failure_reason ?? '-' }}</p>
        </div>
    </div>

    @if(in_array($settlement->status, ['pending', 'approved', 'processing'], true))
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:18px;margin-top:18px;">
            <form method="POST" action="{{ route('mpesa-payment.settlements.approve', $settlement) }}" style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;">
                @csrf
                <h3 style="margin-top:0;">Approve</h3>
                <p style="color:#64748b;">Approve this request before manual payout.</p>
                <button type="submit" class="btn btn-primary" @disabled($settlement->status !== 'pending')>Approve Settlement</button>
            </form>

            <form method="POST" action="{{ route('mpesa-payment.settlements.mark-paid', $settlement) }}" style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;">
                @csrf
                <h3 style="margin-top:0;">Mark Paid</h3>
                <input name="mpesa_receipt_number" placeholder="M-Pesa receipt" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;margin-bottom:10px;">
                <input name="transaction_reference" placeholder="Reference" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;margin-bottom:10px;">
                <textarea name="notes" rows="2" placeholder="Optional notes" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;margin-bottom:10px;"></textarea>
                <button type="submit" class="btn btn-primary">Mark Paid</button>
            </form>

            <form method="POST" action="{{ route('mpesa-payment.settlements.mark-failed', $settlement) }}" style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;">
                @csrf
                <h3 style="margin-top:0;">Mark Failed</h3>
                <textarea name="failure_reason" rows="4" required placeholder="Reason payout failed" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;margin-bottom:10px;"></textarea>
                <button type="submit" class="btn btn-danger">Mark Failed</button>
            </form>
        </div>
    @endif
</div>
@endsection
