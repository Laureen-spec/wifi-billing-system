@extends('layouts.app')

@section('page-title')
    Customer Details
@endsection

@section('content')
<div style="max-width:1100px;margin:0 auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <div>
            <h1 style="margin:0;font-size:30px;">{{ $customer->name }}</h1>
            <p style="margin:6px 0 0;color:#64748b;">
                {{ $customer->username ?? $customer->phone ?? 'No username' }}
            </p>
        </div>

        <div style="display:flex;gap:10px;">
            <a href="{{ route('isp.customers.index') }}" class="btn btn-secondary">Back</a>
            <a href="{{ route('isp.customers.edit', $customer) }}" class="btn btn-primary">Edit</a>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;">
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;">
            <h3>Customer Info</h3>
            <p><strong>Name:</strong> {{ $customer->name }}</p>
            <p><strong>Phone:</strong> {{ $customer->phone ?? '-' }}</p>
            <p><strong>Email:</strong> {{ $customer->email ?? '-' }}</p>
            <p><strong>Location:</strong> {{ $customer->location ?? '-' }}</p>
            <p><strong>Address:</strong> {{ $customer->address ?? '-' }}</p>
        </div>

        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;">
            <h3>Connection</h3>
            <p><strong>Access Type:</strong> {{ strtoupper($customer->access_type ?? 'hotspot') }}</p>
            <p><strong>Username:</strong> {{ $customer->username ?? '-' }}</p>
            <p><strong>Status:</strong> {{ ucfirst($customer->connection_status ?? 'pending') }}</p>
            <p><strong>Shared Users:</strong> {{ $customer->shared_users ?? 1 }}</p>
            <p><strong>Last Online:</strong> {{ $customer->last_online_at?->diffForHumans() ?? '-' }}</p>
        </div>

        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;">
            <h3>Billing</h3>
            <p><strong>Package:</strong> {{ $customer->internetPackage?->name ?? '-' }}</p>
            <p><strong>Billing Status:</strong> {{ ucfirst($customer->billing_status ?? 'unpaid') }}</p>
            <p><strong>Monthly Amount:</strong> KES {{ number_format((float) $customer->monthly_amount, 2) }}</p>
            <p><strong>Installation Date:</strong> {{ $customer->installation_date?->format('Y-m-d') ?? '-' }}</p>
            <p><strong>Next Due Date:</strong> {{ $customer->next_due_date?->format('Y-m-d') ?? '-' }}</p>
        </div>

        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;">
            <h3>MikroTik</h3>
            <p><strong>Router:</strong> {{ $customer->mikrotikRouter?->name ?? '-' }}</p>
            <p><strong>Router Host:</strong> {{ $customer->mikrotikRouter?->host ?? '-' }}</p>
            <p><strong>IP Address:</strong> {{ $customer->ip_address ?? '-' }}</p>
            <p><strong>MAC Address:</strong> {{ $customer->mac_address ?? '-' }}</p>
            <p><strong>Data Used:</strong> {{ $customer->data_used_human ?? number_format(($customer->data_used_bytes ?? 0) / 1024 / 1024, 2) . ' MB' }}</p>
        </div>
    </div>

    <div style="margin-top:18px;background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;">
        <h3>Provisioning</h3>

        <form method="POST" action="{{ route('isp.provisioning.generate') }}">
            @csrf
            <input type="hidden" name="customer_id" value="{{ $customer->id }}">
            <input type="hidden" name="internet_package_id" value="{{ $customer->internet_package_id }}">
            <button type="submit" class="btn btn-primary">Retry / Generate Provisioning</button>
        </form>
    </div>


    @if($customer->relationLoaded('provisioningTokens') && $customer->provisioningTokens->count())
        <div style="margin-top:18px;background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;">
            <h3>Provisioning History</h3>
            <div style="overflow:auto;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th style="text-align:left;padding:10px;border-bottom:1px solid #e5e7eb;">Token</th>
                            <th style="text-align:left;padding:10px;border-bottom:1px solid #e5e7eb;">Router</th>
                            <th style="text-align:left;padding:10px;border-bottom:1px solid #e5e7eb;">Package</th>
                            <th style="text-align:left;padding:10px;border-bottom:1px solid #e5e7eb;">Status</th>
                            <th style="text-align:left;padding:10px;border-bottom:1px solid #e5e7eb;">Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customer->provisioningTokens as $token)
                            <tr>
                                <td style="padding:10px;border-bottom:1px solid #eef2f7;">#{{ $token->id }}</td>
                                <td style="padding:10px;border-bottom:1px solid #eef2f7;">{{ $token->router?->name ?? '-' }}</td>
                                <td style="padding:10px;border-bottom:1px solid #eef2f7;">{{ $token->internet_package_id ?? '-' }}</td>
                                <td style="padding:10px;border-bottom:1px solid #eef2f7;">{{ ucfirst($token->status ?? 'pending') }}</td>
                                <td style="padding:10px;border-bottom:1px solid #eef2f7;">{{ $token->updated_at?->diffForHumans() ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection