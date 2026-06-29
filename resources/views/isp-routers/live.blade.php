@extends('layouts.app')
@section('title', 'MikroTik Live Users & Ports')
@section('page_heading', 'MikroTik Live Users & Ports')
@section('content')
@php
    $counts = $snapshot['counts'] ?? [];
    $totals = $snapshot['totals'] ?? [];
    $fmtBytes = function ($bytes) {
        $bytes = (int) $bytes;
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GiB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MiB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KiB';
        return $bytes . ' B';
    };
@endphp
<div class="page-title">
    <div>
        <h1>{{ $router->name }} Live Users & Ports</h1>
        <p>Reads live users, PPPoE sessions, DHCP leases, bridge hosts, cable status, and port traffic from MikroTik API.</p>
    </div>
    <div class="actions">
        <a class="btn secondary" href="{{ route('isp.routers.show', $router) }}">Back</a>
        <a class="btn" href="{{ route('isp.routers.live', $router) }}">Refresh</a>
    </div>
</div>

@if(!($snapshot['ok'] ?? false))
    <div class="card" style="border-color:#fecaca;background:#fff1f2">
        <h3>API connection failed</h3>
        <p>{{ $snapshot['error'] ?? 'Unknown error' }}</p>
        <p class="muted">Confirm router host/IP, port 8728, username and password in Laravel router settings.</p>
    </div>
@else
<div class="grid grid-4">
    <div class="card"><div class="muted">Hotspot Online</div><div class="stat">{{ $counts['hotspot_active'] ?? 0 }}</div></div>
    <div class="card"><div class="muted">PPPoE Online</div><div class="stat">{{ $counts['pppoe_active'] ?? 0 }}</div></div>
    <div class="card"><div class="muted">DHCP Bound</div><div class="stat">{{ $counts['dhcp_bound'] ?? 0 }}</div></div>
    <div class="card"><div class="muted">Bridge Hosts</div><div class="stat">{{ $counts['bridge_hosts'] ?? 0 }}</div></div>
</div>

<div class="grid grid-2" style="margin-top:18px">
    <div class="card">
        <h3>Router Resource</h3>
        <table>
            <tr><th>Board</th><td>{{ $snapshot['resource']['board-name'] ?? '-' }}</td></tr>
            <tr><th>RouterOS</th><td>{{ $snapshot['resource']['version'] ?? '-' }}</td></tr>
            <tr><th>Uptime</th><td>{{ $snapshot['resource']['uptime'] ?? '-' }}</td></tr>
            <tr><th>CPU Load</th><td>{{ $snapshot['resource']['cpu-load'] ?? '-' }}%</td></tr>
            <tr><th>Free Memory</th><td>{{ isset($snapshot['resource']['free-memory']) ? $fmtBytes($snapshot['resource']['free-memory']) : '-' }}</td></tr>
            <tr><th>Total Memory</th><td>{{ isset($snapshot['resource']['total-memory']) ? $fmtBytes($snapshot['resource']['total-memory']) : '-' }}</td></tr>
        </table>
    </div>
    <div class="card">
        <h3>Data Consumed</h3>
        <table>
            <tr><th>Hotspot Upload</th><td>{{ $fmtBytes($totals['hotspot_bytes_in'] ?? 0) }}</td></tr>
            <tr><th>Hotspot Download</th><td>{{ $fmtBytes($totals['hotspot_bytes_out'] ?? 0) }}</td></tr>
            <tr><th>PPPoE Upload</th><td>{{ $fmtBytes($totals['pppoe_bytes_in'] ?? 0) }}</td></tr>
            <tr><th>PPPoE Download</th><td>{{ $fmtBytes($totals['pppoe_bytes_out'] ?? 0) }}</td></tr>
        </table>
    </div>
</div>

<div class="card" style="margin-top:18px">
    <h3>Live Traffic on Ports</h3>
    <table>
        <thead><tr><th>Port</th><th>RX</th><th>TX</th><th>Packets RX</th><th>Packets TX</th></tr></thead>
        <tbody>
        @forelse(($snapshot['traffic'] ?? []) as $port => $row)
            <tr>
                <td><strong>{{ $port }}</strong></td>
                <td>{{ $row['rx-bits-per-second'] ?? '-' }} bps</td>
                <td>{{ $row['tx-bits-per-second'] ?? '-' }} bps</td>
                <td>{{ $row['rx-packets-per-second'] ?? '-' }}</td>
                <td>{{ $row['tx-packets-per-second'] ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="muted">No traffic data returned.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="card" style="margin-top:18px">
    <h3>Cable / Device Connected on ether Ports</h3>
    <table>
        <thead><tr><th>Port</th><th>Status</th><th>Rate</th><th>Auto Negotiation</th><th>Full Duplex</th></tr></thead>
        <tbody>
        @forelse(($snapshot['port_status'] ?? []) as $port => $row)
            <tr>
                <td><strong>{{ $port }}</strong></td>
                <td><span class="badge {{ ($row['status'] ?? '') === 'link-ok' ? 'ok' : 'warn' }}">{{ $row['status'] ?? '-' }}</span></td>
                <td>{{ $row['rate'] ?? '-' }}</td>
                <td>{{ $row['auto-negotiation'] ?? '-' }}</td>
                <td>{{ $row['full-duplex'] ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="muted">No ethernet monitor data returned.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="grid grid-2" style="margin-top:18px">
    <div class="card">
        <h3>Hotspot Active Users</h3>
        <table>
            <thead><tr><th>User</th><th>IP</th><th>MAC</th><th>Uptime</th><th>Data</th></tr></thead>
            <tbody>
            @forelse(($snapshot['hotspot_active'] ?? []) as $row)
                <tr>
                    <td>{{ $row['user'] ?? '-' }}</td>
                    <td>{{ $row['address'] ?? '-' }}</td>
                    <td>{{ $row['mac-address'] ?? '-' }}</td>
                    <td>{{ $row['uptime'] ?? '-' }}</td>
                    <td>{{ $fmtBytes(($row['bytes-in'] ?? 0) + ($row['bytes-out'] ?? 0)) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">No hotspot users online.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card">
        <h3>PPPoE Active Users</h3>
        <table>
            <thead><tr><th>Name</th><th>Address</th><th>Caller ID</th><th>Uptime</th><th>Service</th></tr></thead>
            <tbody>
            @forelse(($snapshot['ppp_active'] ?? []) as $row)
                <tr>
                    <td>{{ $row['name'] ?? '-' }}</td>
                    <td>{{ $row['address'] ?? '-' }}</td>
                    <td>{{ $row['caller-id'] ?? '-' }}</td>
                    <td>{{ $row['uptime'] ?? '-' }}</td>
                    <td>{{ $row['service'] ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">No PPPoE users online.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="margin-top:18px">
    <h3>Devices Connected Through Ports</h3>
    <table>
        <thead><tr><th>MAC</th><th>Bridge</th><th>On Interface</th><th>Age</th><th>Local</th></tr></thead>
        <tbody>
        @forelse(($snapshot['bridge_hosts'] ?? []) as $row)
            <tr>
                <td>{{ $row['mac-address'] ?? '-' }}</td>
                <td>{{ $row['bridge'] ?? '-' }}</td>
                <td><strong>{{ $row['on-interface'] ?? '-' }}</strong></td>
                <td>{{ $row['age'] ?? '-' }}</td>
                <td>{{ $row['local'] ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="muted">No bridge host data returned. If customer router is NAT/PPPoE, users behind it may be hidden.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="card" style="margin-top:18px">
    <h3>DHCP Bound Devices / Interface Scan Alternative</h3>
    <table>
        <thead><tr><th>Host</th><th>Address</th><th>MAC</th><th>Status</th><th>Server</th></tr></thead>
        <tbody>
        @forelse(($snapshot['dhcp_leases'] ?? []) as $row)
            <tr>
                <td>{{ $row['host-name'] ?? '-' }}</td>
                <td>{{ $row['address'] ?? '-' }}</td>
                <td>{{ $row['mac-address'] ?? '-' }}</td>
                <td>{{ $row['status'] ?? '-' }}</td>
                <td>{{ $row['server'] ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="muted">No bound DHCP devices found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endif
@endsection
