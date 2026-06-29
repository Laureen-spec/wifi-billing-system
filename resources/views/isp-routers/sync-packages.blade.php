@extends('layouts.app')
@section('title', 'Sync Hotspot Packages')
@section('page_heading', 'Sync Hotspot Packages')
@section('content')
<div class="page-title">
    <div>
        <h1>Sync Packages - {{ $router->name }}</h1>
        <p>This creates or updates MikroTik Hotspot user profiles from active internet packages.</p>
    </div>
    <a class="btn secondary" href="{{ route('isp.routers.show', $router) }}">Back</a>
</div>

<div class="card">
    <div class="command-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px">
        <h3>Run This Command in MikroTik</h3>
        <button class="btn secondary copy-command" type="button" data-command="{{ e($command) }}">Copy command</button>
    </div>
    <div class="code command-box" style="white-space:pre-wrap">{{ $command }}</div>
    <p class="muted">Script URL: {{ $syncUrl }}</p>
    <div class="alert success">
        Each package becomes a Hotspot user profile. Device Limit becomes MikroTik shared-users. Speed becomes rate-limit.
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
