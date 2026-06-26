@extends('layouts.app')

@section('title', 'Internet Packages')
@section('page_heading', 'Internet Packages')

@section('content')

<style>
.package-tabs{
    display:flex;
    gap:18px;
    flex-wrap:wrap;
    border-bottom:1px solid var(--line);
    margin:18px 0 20px;
}
.package-tab{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:10px 2px 12px;
    color:#64748b;
    font-weight:700;
    border-bottom:2px solid transparent;
    text-decoration:none;
}
.package-tab:hover{
    color:#ef4444;
}
.package-tab.active{
    color:#ef4444;
    border-bottom-color:#ef4444;
}
.package-count{
    min-width:24px;
    padding:3px 7px;
    border-radius:8px;
    background:#fee2e2;
    color:#b91c1c;
    text-align:center;
    font-size:12px;
    font-weight:700;
}
.package-search{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    flex-wrap:wrap;
    margin-bottom:14px;
}
.package-search input{
    max-width:340px;
}
.package-name small{
    display:block;
    margin-top:4px;
    color:var(--muted);
}
.package-meta{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
    margin-top:6px;
}
.package-type-badge{
    display:inline-flex;
    align-items:center;
    padding:4px 9px;
    border-radius:999px;
    background:#f1f5f9;
    color:#334155;
    font-size:12px;
    font-weight:700;
}
.package-type-badge.hotspot{
    background:#ecfdf5;
    color:#047857;
}
.package-type-badge.pppoe{
    background:#eff6ff;
    color:#1d4ed8;
}
.package-type-badge.data_bundle{
    background:#fef3c7;
    color:#92400e;
}
.package-type-badge.free_trial{
    background:#fce7f3;
    color:#be185d;
}
</style>

@php
    $activeFilter = $activeFilter ?? 'all';
    $searchTerm = $searchTerm ?? '';
    $packageStats = $packageStats ?? [
        'all' => 0,
        'hotspot' => 0,
        'pppoe' => 0,
        'data_bundle' => 0,
        'free_trial' => 0,
    ];

    $packageTypeLabel = function (?string $type): string {
        return match ($type) {
            'pppoe' => 'PPPoE Service',
            'data_bundle' => 'Flex Bundle',
            'free_trial' => 'Starter Access',
            default => 'Hotspot Access',
        };
    };
@endphp

<div class="page-title">
    <div>
        <h1>Internet Packages</h1>
        <p>Manage Hotspot Access, PPPoE Service, Flex Bundle, and Starter Access packages for your clients.</p>
    </div>

    <div class="actions">
        <a class="btn" href="{{ route('isp.packages.create') }}">Create Package</a>
    </div>
</div>

<div class="package-tabs">
    <a class="package-tab {{ $activeFilter === 'all' ? 'active' : '' }}"
       href="{{ route('isp.packages.index', ['tab' => 'all', 'q' => $searchTerm]) }}">
        All
        <span class="package-count">{{ $packageStats['all'] ?? 0 }}</span>
    </a>

    <a class="package-tab {{ $activeFilter === 'hotspot' ? 'active' : '' }}"
       href="{{ route('isp.packages.index', ['tab' => 'hotspot', 'q' => $searchTerm]) }}">
        Hotspot Access
        <span class="package-count">{{ $packageStats['hotspot'] ?? 0 }}</span>
    </a>

    <a class="package-tab {{ $activeFilter === 'pppoe' ? 'active' : '' }}"
       href="{{ route('isp.packages.index', ['tab' => 'pppoe', 'q' => $searchTerm]) }}">
        PPPoE Service
        <span class="package-count">{{ $packageStats['pppoe'] ?? 0 }}</span>
    </a>

    <a class="package-tab {{ $activeFilter === 'data_bundle' ? 'active' : '' }}"
       href="{{ route('isp.packages.index', ['tab' => 'data_bundle', 'q' => $searchTerm]) }}">
        Flex Bundle
        <span class="package-count">{{ $packageStats['data_bundle'] ?? 0 }}</span>
    </a>

    <a class="package-tab {{ $activeFilter === 'free_trial' ? 'active' : '' }}"
       href="{{ route('isp.packages.index', ['tab' => 'free_trial', 'q' => $searchTerm]) }}">
        Starter Access
        <span class="package-count">{{ $packageStats['free_trial'] ?? 0 }}</span>
    </a>
</div>

<form method="GET" action="{{ route('isp.packages.index') }}" class="package-search">
    <input type="hidden" name="tab" value="{{ $activeFilter }}">
    <input type="text" name="q" value="{{ $searchTerm }}" placeholder="Search packages...">

    <button class="btn secondary" type="submit">Search</button>

    @if($searchTerm !== '')
        <a class="btn secondary" href="{{ route('isp.packages.index', ['tab' => $activeFilter]) }}">Clear</a>
    @endif
</form>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Price</th>
                <th>Speed</th>
                <th>Duration</th>
                <th>Type</th>
                <th>Devices</th>
                <th>Enabled</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
            @forelse($packages as $package)
                @php
                    $type = $package->package_type ?? $package->access_type ?? 'hotspot';
                @endphp

                <tr>
                    <td class="package-name">
                        <strong>{{ $package->name }}</strong>
                        <small>{{ ucfirst($package->billing_cycle) }} billing</small>

                        <div class="package-meta">
                            @if($package->enable_burst)
                                <span class="badge ok">Burst</span>
                            @endif

                            @if($package->enable_schedule)
                                <span class="badge warn">Scheduled</span>
                            @endif

                            @if($package->hidden_from_client)
                                <span class="badge bad">Hidden</span>
                            @endif
                        </div>
                    </td>

                    <td>KES {{ number_format((float) $package->price, 2) }}</td>

                    <td>
                        {{ $package->download_speed_mbps ?: '-' }}M /
                        {{ $package->upload_speed_mbps ?: '-' }}M
                    </td>

                    <td>
                        {{ $package->validity_days }}
                        {{ (int) $package->validity_days === 1 ? 'day' : 'days' }}
                    </td>

                    <td>
                        <span class="package-type-badge {{ $type }}">
                            {{ $packageTypeLabel($type) }}
                        </span>
                    </td>

                    <td>
                        @if($package->available_on_all_mikrotik)
                            All
                        @else
                            {{ $package->mikrotikRouters->count() }}
                        @endif
                    </td>

                    <td>
                        <span class="badge {{ $package->status === 'active' ? 'ok' : 'bad' }}">
                            {{ $package->status === 'active' ? 'Yes' : 'No' }}
                        </span>
                    </td>

                    <td>
                        <a class="btn secondary" href="{{ route('isp.packages.edit', $package) }}">Edit</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="muted">No packages found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination">{{ $packages->links() }}</div>
@endsection