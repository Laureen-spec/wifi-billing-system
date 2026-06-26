@extends('layouts.app')
@section('title', 'MikroTik Details')
@section('page_heading', 'MikroTik Details')
@section('content')
@php
    $cpu = $router->cpu_load ?? $router->cpu_usage;
    $memory = 'Unknown';
    if ($router->memory_free !== null && $router->memory_total !== null && (int) $router->memory_total > 0) {
        $memory = number_format(((int) $router->memory_free) / 1048576, 1) . ' / ' . number_format(((int) $router->memory_total) / 1048576, 1) . ' MiB free';
    } elseif ($router->memory_usage) {
        $memory = $router->memory_usage . ' MiB used';
    }

    $isOnline = $router->last_seen_at && $router->last_seen_at->gt(now()->subSeconds(60));
    $liveStatus = ! $router->last_seen_at ? ['Waiting for Link', 'warn'] : ($isOnline ? ['Online', 'ok'] : ['Offline', 'bad']);
    $hotspotFiles = match ($router->hotspot_files_status) {
        'present' => ['Present', 'ok'],
        'missing' => ['Missing', 'bad'],
        default => ['Unknown', 'warn'],
    };
    $winboxEndpoint = trim((string) $router->winbox_endpoint);
    $hasWinbox = $winboxEndpoint !== ''
        && ! str_contains($winboxEndpoint, 'pending-link')
        && in_array($router->remote_winbox_status, ['enabled', 'available'], true);
@endphp
<div class="page-title">
    <div>
        <h1>{{ $router->name }}</h1>
        <p>Full MikroTik device information, service status, and remote Winbox access.</p>
    </div>
    <div class="actions">
        <a class="btn secondary" href="{{ route('isp.routers.index') }}">Back</a>
        <a class="btn secondary" href="{{ route('isp.routers.edit', $router) }}">Edit</a>
        <a class="btn" href="{{ route('isp.routers.setup-script', $router) }}">Provisioning Command</a>
    </div>
</div>

<div class="grid grid-4">
    <div class="card"><div class="muted">Board Name</div><div class="stat" style="font-size:20px">{{ $router->board_name ?: '-' }}</div></div>
    <div class="card"><div class="muted">RouterOS</div><div class="stat" style="font-size:20px">{{ $router->routeros_version ?: '-' }}</div></div>
    <div class="card"><div class="muted">CPU</div><div class="stat" style="font-size:20px">{{ $cpu !== null ? $cpu.'%' : '-' }}</div></div>
    <div class="card"><div class="muted">Status</div><div class="stat" style="font-size:20px"><span class="badge {{ $liveStatus[1] }}">{{ $liveStatus[0] }}</span></div></div>
</div>

<div class="grid grid-2" style="margin-top:18px">
    <div class="card">
        <h3>Live RouterOS Status</h3>
        <table>
            <tr><th>Board Name</th><td>{{ $router->board_name ?: '-' }}</td></tr>
            <tr><th>RouterOS Version</th><td>{{ $router->routeros_version ?: '-' }}</td></tr>
            <tr><th>CPU</th><td>{{ $cpu !== null ? $cpu . '%' : 'Unknown' }}</td></tr>
            <tr><th>Memory</th><td>{{ $memory }}</td></tr>
            <tr><th>Uptime</th><td>{{ $router->uptime ?: '-' }}</td></tr>
            <tr><th>Last Seen</th><td>{{ $router->last_seen_at?->format('Y-m-d H:i:s') ?: 'No heartbeat yet' }}</td></tr>
            <tr><th>Router Time</th><td>{{ $router->router_time ?: '-' }}</td></tr>
        </table>
    </div>
    <div class="card">
        <h3>Device Information</h3>
        <table>
            <tr><th>Identity</th><td>{{ $router->identity ?: $router->name }}</td></tr>
            <tr><th>Architecture</th><td>{{ $router->architecture ?: '-' }}</td></tr>
            <tr><th>Host</th><td>{{ $router->host }}:{{ $router->api_port }}</td></tr>
            <tr><th>Provisioning</th><td>{{ ucfirst(str_replace('_', ' ', $router->provision_status ?? 'pending')) }}</td></tr>
            <tr><th>Downloaded At</th><td>{{ $router->provisioned_at?->format('Y-m-d H:i:s') ?: '-' }}</td></tr>
        </table>
    </div>
</div>

<div class="grid grid-2" style="margin-top:18px">
    <div class="card">
        <h3>Service Status</h3>
        <table>
            <tr><th>Hotspot Files</th><td><span class="badge {{ $hotspotFiles[1] }}">{{ $hotspotFiles[0] }}</span></td></tr>
            <tr><th>Hotspot</th><td><span class="badge {{ $router->hotspot_status === 'present' ? 'ok' : 'warn' }}">{{ ucfirst(str_replace('_', ' ', $router->hotspot_status ?? 'unknown')) }}</span></td></tr>
            <tr><th>PPPoE</th><td><span class="badge {{ $router->pppoe_status === 'present' ? 'ok' : 'warn' }}">{{ ucfirst(str_replace('_', ' ', $router->pppoe_status ?? 'unknown')) }}</span></td></tr>
            <tr><th>Sync</th><td>{{ ucfirst(str_replace('_', ' ', $router->sync_status ?? 'pending')) }}</td></tr>
            <tr><th>Time Sync</th><td>{{ ucfirst(str_replace('_', ' ', $router->time_sync_status ?? 'pending')) }}</td></tr>
        </table>
    </div>
    <div class="card">
        <h3>Remote Winbox Availability</h3>
        <table>
            <tr><th>Status</th><td><span class="badge {{ $hasWinbox ? 'ok' : '' }}">{{ $hasWinbox ? 'Available' : 'Not available' }}</span></td></tr>
            <tr><th>Endpoint</th><td>{{ $hasWinbox ? $winboxEndpoint : 'Not available' }}</td></tr>
            <tr><th>Username</th><td>{{ $hasWinbox ? ($router->winbox_username ?: '-') : '-' }}</td></tr>
            <tr><th>Message</th><td>{{ $router->remote_winbox_error ?: 'Remote Winbox is only shown when a real endpoint is available.' }}</td></tr>
        </table>
    </div>
</div>

<div class="card" style="margin-top:18px">
    <h3>Remote Winbox Access</h3>
    <div class="grid grid-3">
        <div><strong>Endpoint</strong><div class="muted">{{ $hasWinbox ? $winboxEndpoint : 'Not available' }}</div></div>
        <div><strong>Username</strong><div class="muted">{{ $hasWinbox ? ($router->winbox_username ?: '-') : '-' }}</div></div>
        <div><strong>Password</strong><div class="code" style="padding:8px">{{ $hasWinbox && $router->winbox_password ? $router->winbox_password : 'Not available until remote Winbox is enabled on a real endpoint.' }}</div></div>
    </div>
    <div class="actions" style="margin-top:14px">
        <form method="POST" action="{{ route('isp.routers.enable-winbox', $router) }}">@csrf<button class="btn secondary">Enable Winbox</button></form>
        <form method="POST" action="{{ route('isp.routers.regenerate-winbox', $router) }}">@csrf<button class="btn secondary" onclick="return confirm('Regenerate the Winbox password?')">Regenerate Winbox</button></form>
    </div>
</div>

<div class="card" style="margin-top:18px">
    <h3>Actions</h3>
    <div class="actions">
        <form method="POST" action="{{ route('isp.routers.reprovision', $router) }}">@csrf<button class="btn secondary" onclick="return confirm('Generate a new provisioning command?')">Reprovision</button></form>
        <form method="POST" action="{{ route('isp.routers.sync-hotspot', $router) }}">@csrf<button class="btn secondary">Sync hotspot files</button></form>
        <form method="POST" action="{{ route('isp.routers.sync-time', $router) }}">@csrf<button class="btn secondary">Sync Router Time</button></form>
        <form method="POST" action="{{ route('isp.routers.destroy', $router) }}">@csrf @method('DELETE')<button class="btn danger" onclick="return confirm('Delete this MikroTik router?')">Delete</button></form>
    </div>
</div>
@endsection
