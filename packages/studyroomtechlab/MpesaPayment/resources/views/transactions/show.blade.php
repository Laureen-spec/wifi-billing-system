@extends('layouts.app')

@section('page-title')
    M-Pesa Transaction Details
@endsection

@section('content')
<div style="max-width:1100px;margin:0 auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <div>
            <h1 style="font-size:28px;margin:0;">M-Pesa Transaction #{{ $transaction->id }}</h1>
            <p style="margin:6px 0 0;color:#64748b;">Receipt, callback, wallet posting, and provisioning status.</p>
        </div>
        <div style="display:flex;gap:10px;">
            <a href="{{ route('mpesa-payment.transactions.index') }}" class="btn btn-secondary">Back</a>
            <a href="{{ route('mpesa-payment.dashboard') }}" class="btn btn-primary">Dashboard</a>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;">
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;">
            <h3 style="margin-top:0;">Payment Info</h3>
            <p><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $transaction->status)) }}</p>
            <p><strong>Phone:</strong> {{ $transaction->phone ?? '-' }}</p>
            <p><strong>Amount:</strong> KES {{ number_format((float) $transaction->amount, 2) }}</p>
            <p><strong>Currency:</strong> {{ $transaction->currency ?? 'KES' }}</p>
            <p><strong>Receipt:</strong> {{ $transaction->mpesa_receipt_number ?? '-' }}</p>
            <p><strong>Payment Type:</strong> {{ ucfirst(str_replace('_', ' ', $transaction->payment_type ?? 'stk_push')) }}</p>
            <p><strong>Collection Mode:</strong> {{ ucfirst(str_replace('_', ' ', $transaction->collection_mode ?? 'platform')) }}</p>
            <p><strong>Environment:</strong> {{ ucfirst($transaction->environment ?? '-') }}</p>
        </div>

        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;">
            <h3 style="margin-top:0;">WiFi Billing Link</h3>
            <p><strong>ISP ID:</strong> {{ $transaction->isp_id ?? '-' }}</p>
            <p><strong>Customer ID:</strong> {{ $transaction->customer_id ?? '-' }}</p>
            <p><strong>Package ID:</strong> {{ $transaction->internet_package_id ?? '-' }}</p>
            <p><strong>Router ID:</strong> {{ $transaction->mikrotik_router_id ?? '-' }}</p>
            <p><strong>Provisioning Triggered:</strong> {{ $transaction->provisioning_triggered ? 'Yes' : 'No' }}</p>
            <p><strong>Provisioning Token ID:</strong> {{ $transaction->provisioning_token_id ?? '-' }}</p>
            <p><strong>Provisioned At:</strong> {{ $transaction->provisioned_at?->format('Y-m-d H:i:s') ?? '-' }}</p>
        </div>

        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;">
            <h3 style="margin-top:0;">M-Pesa Request IDs</h3>
            <p><strong>Merchant Request ID:</strong> {{ $transaction->merchant_request_id ?? '-' }}</p>
            <p><strong>Checkout Request ID:</strong> {{ $transaction->checkout_request_id ?? '-' }}</p>
            <p><strong>Result Code:</strong> {{ $transaction->result_code ?? '-' }}</p>
            <p><strong>Result Description:</strong> {{ $transaction->result_desc ?? '-' }}</p>
            <p><strong>Account Reference:</strong> {{ $transaction->account_reference ?? '-' }}</p>
            <p><strong>Transaction Desc:</strong> {{ $transaction->transaction_desc ?? '-' }}</p>
        </div>

        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;">
            <h3 style="margin-top:0;">Wallet Split</h3>
            <p><strong>Platform Fee:</strong> KES {{ number_format((float) $transaction->platform_fee, 2) }}</p>
            <p><strong>ISP Amount:</strong> KES {{ number_format((float) $transaction->isp_amount, 2) }}</p>
            <p><strong>Wallet Posted:</strong> {{ $transaction->wallet_posted ? 'Yes' : 'No' }}</p>
            <p><strong>Paid At:</strong> {{ $transaction->paid_at?->format('Y-m-d H:i:s') ?? '-' }}</p>
            <p><strong>Failed At:</strong> {{ $transaction->failed_at?->format('Y-m-d H:i:s') ?? '-' }}</p>
            <p><strong>Created At:</strong> {{ $transaction->created_at?->format('Y-m-d H:i:s') ?? '-' }}</p>
        </div>
    </div>

    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;margin-top:18px;">
        <h3 style="margin-top:0;">Callback Payload</h3>
        <pre style="background:#f8fafc;color:#0f172a;border:1px solid #e5e7eb;border-radius:14px;padding:16px;overflow:auto;max-height:360px;">{{ json_encode($transaction->callback_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: 'No callback payload yet.' }}</pre>
    </div>

    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;margin-top:18px;">
        <h3 style="margin-top:0;">Response Payload</h3>
        <pre style="background:#f8fafc;color:#0f172a;border:1px solid #e5e7eb;border-radius:14px;padding:16px;overflow:auto;max-height:360px;">{{ json_encode($transaction->response_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: 'No response payload yet.' }}</pre>
    </div>
</div>
@endsection
