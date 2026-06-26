@extends('layouts.app')
@section('title', 'Provisioning Command')
@section('page_heading', 'Provisioning Command')
@section('content')
<div class="page-title">
    <div>
        <h1>Provisioning Command</h1>
        <p>Copy this command into MikroTik Terminal for {{ $token->customer?->name ?: 'customer' }}.</p>
    </div>
    <a class="btn secondary" href="{{ route('isp.provisioning.index') }}">Back</a>
</div>

<div class="grid grid-3" style="margin-bottom:18px">
    <div class="card">
        <div class="muted">Customer</div>
        <div class="stat" style="font-size:20px">{{ $token->customer?->name ?: '-' }}</div>
    </div>
    <div class="card">
        <div class="muted">Router</div>
        <div class="stat" style="font-size:20px">{{ $token->router?->name ?: '-' }}</div>
    </div>
    <div class="card">
        <div class="muted">Status</div>
        <div class="stat" style="font-size:20px">{{ ucfirst($token->status) }}</div>
    </div>
</div>

<div class="card" style="margin-bottom:18px">
    <h3>Public Provisioning URL</h3>
    <div class="code">{{ $provisionUrl }}</div>
    <p class="muted">This route is public so the MikroTik router can fetch it without login.</p>
</div>

<div class="card" style="margin-bottom:18px">
    <h3>MikroTik Fetch and Import Command</h3>
    <div class="code">{{ $command }}</div>
    <p class="muted">Destination file: studyroom_provision.rsc</p>
    @if($token->status === 'active')
        <form method="POST" action="{{ route('isp.provisioning.deactivate', $token->token) }}" style="margin-top:12px">
            @csrf
            <button class="btn danger" type="submit">Deactivate Token</button>
        </form>
    @endif
</div>

<div class="card" style="margin-bottom:18px">
    <h3>Token Details</h3>
    <div class="grid grid-3">
        <div><strong>Token</strong><div class="muted">{{ $token->token }}</div></div>
        <div><strong>Package</strong><div class="muted">{{ $token->internetPackage?->name ?: '-' }}</div></div>
        <div><strong>Expires</strong><div class="muted">{{ $token->expires_at?->format('Y-m-d H:i') ?: 'No expiry' }}</div></div>
    </div>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Status</th>
                <th>IP Address</th>
                <th>Message</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                    <td><span class="badge {{ $log->status === 'served' ? 'ok' : 'bad' }}">{{ $log->status }}</span></td>
                    <td>{{ $log->ip_address ?: '-' }}</td>
                    <td>{{ $log->message }}<div class="muted">{{ $log->user_agent }}</div></td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">No provisioning logs yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="pagination">{{ $logs->links() }}</div>
@endsection
