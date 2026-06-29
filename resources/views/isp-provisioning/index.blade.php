@extends('layouts.app')
@section('title', 'Provisioning')
@section('page_heading', 'Provisioning')
@section('content')
<div class="page-title">
    <div>
        <h1>Provisioning</h1>
        <p>Generate MikroTik fetch/import commands for customer hotspot access.</p>
    </div>
</div>

<div class="form" style="margin-bottom:18px">
    <form method="POST" action="{{ route('isp.provisioning.generate') }}">
        @csrf
        <div class="grid grid-4">
            <div class="field">
                <label for="customer_id">Customer</label>
                <select id="customer_id" name="customer_id" required>
                    <option value="">Select customer</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }}</option>
                    @endforeach
                </select>
                @error('customer_id')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="mikrotik_router_id">Router</label>
                <select id="mikrotik_router_id" name="mikrotik_router_id">
                    <option value="">Use first router</option>
                    @foreach($routers as $router)
                        <option value="{{ $router->id }}" @selected(old('mikrotik_router_id') == $router->id)>{{ $router->name }}</option>
                    @endforeach
                </select>
                @error('mikrotik_router_id')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="internet_package_id">Package</label>
                <select id="internet_package_id" name="internet_package_id">
                    <option value="">Use customer package</option>
                    @foreach($packages as $package)
                        <option value="{{ $package->id }}" @selected(old('internet_package_id') == $package->id)>{{ $package->name }}</option>
                    @endforeach
                </select>
                @error('internet_package_id')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="expires_at">Expires At</label>
                <input id="expires_at" type="datetime-local" name="expires_at" value="{{ old('expires_at') }}">
                @error('expires_at')<div class="error">{{ $message }}</div>@enderror
            </div>
        </div>
        <button class="btn" type="submit">Generate Provisioning</button>
    </form>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Customer</th>
                <th>Router</th>
                <th>Package</th>
                <th>Status</th>
                <th>Used</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tokens as $token)
                <tr>
                    <td><strong>{{ $token->customer?->name ?: '-' }}</strong><div class="muted">{{ $token->isp?->name }}</div></td>
                    <td>{{ $token->router?->name ?: '-' }}</td>
                    <td>{{ $token->internetPackage?->name ?: '-' }}</td>
                    <td><span class="badge {{ $token->status === 'active' ? 'ok' : 'bad' }}">{{ ucfirst($token->status) }}</span><div class="muted">{{ $token->expires_at ? 'Expires '.$token->expires_at->format('Y-m-d H:i') : 'No expiry' }}</div></td>
                    <td>{{ $token->used_at?->format('Y-m-d H:i') ?: '-' }}</td>
                    <td><a class="btn secondary" href="{{ route('isp.provisioning.show', $token->token) }}">Command</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">No provisioning tokens found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="pagination">{{ $tokens->links() }}</div>
@endsection
