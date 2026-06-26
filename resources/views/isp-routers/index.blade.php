@extends('layouts.app')
@section('title', 'MikroTik Routers')
@section('page_heading', 'MikroTik Routers')
@section('content')
@php
    $formatMemory = function ($router) {
        if ($router->memory_free !== null && $router->memory_total !== null && (int) $router->memory_total > 0) {
            $free = number_format(((int) $router->memory_free) / 1048576, 1);
            $total = number_format(((int) $router->memory_total) / 1048576, 1);

            return "{$free} / {$total} MiB free";
        }

        return $router->memory_usage ? $router->memory_usage . ' MiB used' : 'Unknown';
    };

    $routerState = function ($router) {
        if (! $router->last_seen_at) {
            return ['Waiting for Link', 'warn'];
        }

        return $router->last_seen_at->gt(now()->subSeconds(60))
            ? ['Online', 'ok']
            : ['Offline', 'bad'];
    };

    $hotspotFilesLabel = function ($router) {
        return match ($router->hotspot_files_status) {
            'present' => ['Present', 'ok'],
            'missing' => ['Missing', 'bad'],
            default => ['Unknown', 'warn'],
        };
    };

    $remoteWinbox = function ($router) {
        $endpoint = trim((string) $router->winbox_endpoint);
        $isRealEndpoint = $endpoint !== '' && ! str_contains($endpoint, 'pending-link');
        $isAvailable = in_array($router->remote_winbox_status, ['enabled', 'available'], true);

        return $isRealEndpoint && $isAvailable
            ? [$endpoint, 'ok']
            : ['Not available', ''];
    };
@endphp

<div class="page-title">
    <div>
        <h1>MikroTik Routers</h1>
        <p>Live RouterOS health from the 20-second heartbeat scheduler.</p>
    </div>
    <div class="actions">
        <a class="btn secondary" href="{{ route('isp.routers.index') }}">Tutorial</a>
        <a class="btn" href="{{ route('isp.routers.create') }}">Link a MikroTik</a>
    </div>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Board Name</th>
                <th>RouterOS</th>
                <th>CPU</th>
                <th>Memory</th>
                <th>Status</th>
                <th>Last Seen</th>
                <th>Hotspot Files</th>
                <th>Remote Winbox</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse($routers as $router)
            @php
                [$statusLabel, $statusClass] = $routerState($router);
                [$filesLabel, $filesClass] = $hotspotFilesLabel($router);
                [$winboxLabel, $winboxClass] = $remoteWinbox($router);
                $cpu = $router->cpu_load ?? $router->cpu_usage;
            @endphp
            <tr>
                <td>
                    <strong>{{ $router->board_name ?: $router->name }}</strong>
                    <div class="muted">{{ $router->identity ?: $router->name }}</div>
                </td>
                <td>{{ $router->routeros_version ?: 'Unknown' }}</td>
                <td>
                    <span class="badge {{ $cpu !== null && $cpu < 80 ? 'ok' : ($cpu !== null ? 'warn' : '') }}">
                        {{ $cpu !== null ? $cpu . '%' : 'Unknown' }}
                    </span>
                </td>
                <td>{{ $formatMemory($router) }}</td>
                <td>
                    <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    <div class="muted">Script: {{ ucfirst(str_replace('_', ' ', $router->provision_status ?? 'pending')) }}</div>
                </td>
                <td>{{ $router->last_seen_at?->diffForHumans() ?: 'No heartbeat yet' }}</td>
                <td>
                    <span class="badge {{ $filesClass }}">{{ $filesLabel }}</span>
                    @if($router->hotspot_files_status === 'missing')
                        <div class="muted">Hotspot files missing</div>
                    @endif
                </td>
                <td>
                    <span class="badge {{ $winboxClass }}">{{ $winboxLabel }}</span>
                    @if($router->remote_winbox_error)
                        <div class="muted">{{ $router->remote_winbox_error }}</div>
                    @endif
                </td>
                <td>
                    <div class="actions router-actions">
                        <a class="btn secondary" href="{{ route('isp.routers.show', $router) }}">View</a>
                        <a class="btn secondary" href="{{ route('isp.routers.setup-script', $router) }}">Command</a>
                        @if($router->hotspot_files_status === 'missing')
                            <form method="POST" action="{{ route('isp.routers.sync-hotspot', $router) }}">
                                @csrf
                                <button class="btn secondary">Sync Hotspot Files</button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="9" class="muted">No routers found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="pagination">{{ $routers->links() }}</div>

<script>
setInterval(() => {
    const active = document.activeElement;
    const isEditing = active && ['INPUT', 'SELECT', 'TEXTAREA', 'BUTTON', 'A'].includes(active.tagName);
    const hoveringActions = document.querySelector('.router-actions:hover');

    if (!isEditing && !hoveringActions) {
        window.location.reload();
    }
}, 20000);
</script>
@endsection
