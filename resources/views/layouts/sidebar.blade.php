@php
    $user = auth()->user();
    $isPlatform = $user && (in_array($user->type, ['superadmin', 'super_admin', 'control_isp'], true) || $user->hasAnyRole(['superadmin', 'super_admin', 'control_isp']));
@endphp
<aside class="sidebar">
    <div class="brand">
        <strong>StudyRoom TechLab</strong>
        <span>StudyRoom ISP Billing</span>
    </div>
    <nav class="nav">
        <a class="{{ request()->routeIs('billing.dashboard') ? 'active' : '' }}" href="{{ route('billing.dashboard') }}">Dashboard</a>
        @if($isPlatform)
            <a class="{{ request()->routeIs('isps.*') ? 'active' : '' }}" href="{{ route('isps.index') }}">ISPs</a>
        @endif
        <a class="{{ request()->routeIs('isp.packages.*') ? 'active' : '' }}" href="{{ route('isp.packages.index') }}">Internet Packages</a>
        <a class="{{ request()->routeIs('isp.customers.*') ? 'active' : '' }}" href="{{ route('isp.customers.index') }}">Customers</a>
        <a class="{{ request()->routeIs('isp.routers.*') ? 'active' : '' }}" href="{{ route('isp.routers.index') }}">MikroTik Routers</a>
        @if(Route::has('settings.index'))
            <a href="{{ route('settings.index') }}">Settings</a>
        @endif
    </nav>
</aside>
