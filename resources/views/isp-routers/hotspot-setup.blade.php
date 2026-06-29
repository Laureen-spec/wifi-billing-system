@extends('layouts.app')
@section('title', 'Setup Hotspot')
@section('page_heading', 'Setup Hotspot')
@section('content')
<div class="page-title">
    <div>
        <h1>Setup Hotspot - {{ $router->name }}</h1>
        <p>Select RouterOS version and Hotspot interface, then run the generated command in MikroTik terminal.</p>
    </div>
    <div class="actions">
        <a class="btn secondary" href="{{ route('isp.routers.show', $router) }}">Back</a>
    </div>
</div>

<div class="card" style="margin-bottom:18px">
    <h3>Hotspot Setup Options</h3>
    <form method="GET" action="{{ route('isp.routers.hotspot-setup', $router) }}" class="form">
        <div class="grid grid-3">
            <label>RouterOS Version
                <select name="ros">
                    <option value="6" @selected(($routerOsMajor ?? '6') === '6')>RouterOS 6.x / older</option>
                    <option value="7" @selected(($routerOsMajor ?? '6') === '7')>RouterOS 7.x</option>
                </select>
            </label>
            <label>Hotspot Interface
                <select name="interface">
                    @foreach(['ether4','ether3','ether2','bridgeLocal','bridge','wlan1'] as $interface)
                        <option value="{{ $interface }}" @selected(($data['interface'] ?? 'ether4') === $interface)>{{ $interface }}</option>
                    @endforeach
                </select>
            </label>
            <label>Upstream / Internet Interface
                <input name="upstream" value="{{ $data['upstream'] ?? 'bridgeLocal' }}">
            </label>
            <label>Gateway IP
                <input name="gateway" value="{{ $data['gateway'] ?? '10.10.50.1' }}">
            </label>
            <label>Network CIDR
                <input name="network" value="{{ $data['network'] ?? '10.10.50.0/24' }}">
            </label>
            <label>DNS Name
                <input name="dns_name" value="{{ $data['dns_name'] ?? 'studyroom.hotspot' }}">
            </label>
            <label>Pool Start
                <input name="pool_start" value="{{ $data['pool_start'] ?? '10.10.50.10' }}">
            </label>
            <label>Pool End
                <input name="pool_end" value="{{ $data['pool_end'] ?? '10.10.50.250' }}">
            </label>
        </div>
        <div class="actions" style="margin-top:14px">
            <button class="btn">Generate Setup Command</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="command-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px">
        <h3>Run This Command in MikroTik</h3>
        <button class="btn secondary copy-command" type="button" data-command="{{ e($command) }}">Copy command</button>
    </div>
    <div class="code command-box" style="white-space:pre-wrap">{{ $command }}</div>
    <p class="muted">Script URL: {{ $setupUrl }}</p>
    <div class="alert success">
        Recommended safe test: MikroTik ether4 → Tenda LAN port, Tenda AP mode, Tenda DHCP OFF. Phone should receive 10.10.50.x.
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
