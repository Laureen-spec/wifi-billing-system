@extends('layouts.app')
@section('title', 'MikroTik Provisioning Command')
@section('page_heading', 'MikroTik Provisioning Command')
@section('content')
@php
    preg_match('/\[services:([^\]]+)\]/', (string) $router->notes, $serviceMatch);
    $services = isset($serviceMatch[1]) ? array_filter(explode(',', $serviceMatch[1])) : ['pppoe', 'hotspot'];
    $supportsPppoe = in_array('pppoe', $services, true);
    $supportsHotspot = in_array('hotspot', $services, true);
@endphp
<div class="router-onboarding">
    <div class="page-title">
        <div>
            <h1>{{ $router->name }}</h1>
            <p>Copy this command into MikroTik Terminal, then confirm service setup.</p>
        </div>
        <a class="btn secondary" href="{{ route('isp.routers.index') }}">Back</a>
    </div>

    <div class="setup-steps">
        <div class="setup-step done"><span>1</span><strong>Connection / MikroTik Identity</strong><small>Basic device information saved</small></div>
        <div class="setup-step active"><span>2</span><strong>Device Details / Provisioning Command</strong><small>Copy command into MikroTik</small></div>
        <div class="setup-step {{ $router->last_seen_at && $router->last_seen_at->gt(now()->subSeconds(60)) ? 'done' : '' }}"><span>3</span><strong>Heartbeat Online</strong><small>Router reports every 20 seconds</small></div>
    </div>

    <div class="card provisioning-card">
        <form method="GET" action="{{ route('isp.routers.setup-script', $router) }}" style="margin-bottom:16px">
            <label style="display:block;font-weight:700;margin-bottom:8px">RouterOS version</label>
            <select name="ros" onchange="this.form.submit()" style="width:100%;max-width:420px;padding:12px 14px;border:1px solid #d8dee8;border-radius:10px;background:#fff;font-size:14px">
                <option value="6" @selected(($routerOsMajor ?? '6') === '6')>RouterOS 6.x / older - hAP lite, v6.49</option>
                <option value="7" @selected(($routerOsMajor ?? '6') === '7')>RouterOS 7.x</option>
            </select>
            <p class="muted" style="margin-top:8px">
                Select RouterOS 6.x / older for devices that show <code>bad command name run</code> or use RouterOS 6.49.
            </p>
        </form>
        <div class="command-header">
            <h3>Provisioning Command</h3>
            <button class="btn secondary copy-command" type="button" data-command="{{ e($command) }}">Copy command</button>
        </div>
        <div class="code command-box">{{ $command }}</div>
        <p class="muted">Public script URL: {{ $provisionUrl }}</p>
        <p class="muted">The script creates or replaces a RouterOS scheduler named <strong>studyroom-heartbeat</strong>. Router health is marked online only after the heartbeat reaches this system.</p>
        <div class="alert success device-mode-help">
            If the device reports "device mode not allowed", open the MikroTik terminal and run <code>/system/device-mode/update mode=advanced</code>, unplug power for 10 seconds, then retry the provisioning command.
        </div>
        <div class="terminal-wait">$ Waiting for heartbeat...</div>
    </div>

    <div class="page-title service-title">
        <div>
            <h1>Service Setup</h1>
            <p>Use this router for PPPoE only, Hotspot only, or both services.</p>
        </div>
    </div>

    <div class="grid grid-2 service-card-grid">
        <div class="card service-card">
            <div class="service-card-head">
                <h3>PPPoE</h3>
                <span class="badge {{ $supportsPppoe ? 'ok' : 'warn' }}">{{ $supportsPppoe ? 'Enabled' : 'Not selected' }}</span>
            </div>
            <p class="muted">PPPoE customer provisioning can use this router when PPPoE is selected for the device.</p>
            @if($supportsPppoe)
                <span class="btn secondary">PPPoE configured</span>
            @else
                <a class="btn secondary" href="{{ route('isp.routers.edit', $router) }}">Enable PPPoE</a>
            @endif
        </div>
        <div class="card service-card">
            <div class="service-card-head">
                <h3>Hotspot</h3>
                <span class="badge {{ $supportsHotspot ? 'ok' : 'warn' }}">{{ $supportsHotspot ? 'Enabled' : 'Not selected' }}</span>
            </div>
            <p class="muted">Hotspot customer provisioning can use this router when Hotspot is selected for the device.</p>
            @if($supportsHotspot)
                <span class="btn secondary">Hotspot configured</span>
            @else
                <a class="btn secondary" href="{{ route('isp.routers.edit', $router) }}">Enable Hotspot</a>
            @endif
        </div>
    </div>

    <div class="wizard-actions">
        <a class="btn secondary" href="{{ route('isp.routers.edit', $router) }}">Previous Step</a>
        <a class="btn" href="{{ route('isp.routers.index') }}">Finish</a>
    </div>
</div>
<script>
document.querySelectorAll('.copy-command').forEach((button) => {
    button.addEventListener('click', async () => {
        await navigator.clipboard.writeText(button.dataset.command || '');
        button.textContent = 'Copied';
        setTimeout(() => button.textContent = 'Copy command', 1400);
    });
});
</script>
@endsection
