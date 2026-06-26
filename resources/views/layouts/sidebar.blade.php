@php
    $user = auth()->user();
    $isPlatform = $user && (in_array($user->type, ['superadmin', 'super_admin', 'control_isp'], true) || $user->hasAnyRole(['superadmin', 'super_admin', 'control_isp']));
    $link = fn ($route, $label, $icon = '') => Route::has($route)
        ? '<a class="' . (request()->routeIs($route) ? 'active' : '') . '" href="' . route($route) . '">' . $icon . '<span>' . e($label) . '</span></a>'
        : '';
@endphp
<aside class="sidebar">
    <div class="brand">
        <strong>StudyRoom Connect</strong>
    </div>
    <div class="menu-search">
        <input type="search" placeholder="Search menu..." aria-label="Search menu">
    </div>
    <nav class="nav">
        <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            <span>Dashboard</span>
        </a>
        @if(Route::has('users.index'))
            <a class="{{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                <span>User Management</span>
            </a>
        @endif
        @if(Route::has('sales-proposals.index'))
            <a class="{{ request()->routeIs('sales-proposals.*') ? 'active' : '' }}" href="{{ route('sales-proposals.index') }}">
                <svg viewBox="0 0 24 24"><path d="M7 7h10v10H7z"></path><path d="M4 4h6"></path><path d="M14 20h6"></path><path d="M4 20h6"></path><path d="M14 4h6"></path></svg>
                <span>Proposal</span>
            </a>
        @endif
        <div class="nav-group">
            <div class="nav-parent {{ request()->routeIs('wifi-billing.*') || request()->routeIs('isp.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><path d="M5 13a10 10 0 0 1 14 0"></path><path d="M8.5 16.5a5 5 0 0 1 7 0"></path><path d="M12 20h.01"></path></svg>
                <span>WiFi Billing</span>
            </div>
            <div class="nav-children">
                <a class="{{ request()->routeIs('wifi-billing.dashboard') ? 'active' : '' }}" href="{{ route('wifi-billing.dashboard') }}">
                    <svg viewBox="0 0 24 24"><path d="M5 13a10 10 0 0 1 14 0"></path><path d="M8.5 16.5a5 5 0 0 1 7 0"></path><path d="M12 20h.01"></path></svg>
                    <span>Dashboard</span>
                </a>
                <a class="{{ request()->routeIs('isp.packages.*') ? 'active' : '' }}" href="{{ route('isp.packages.index') }}">
                    <svg viewBox="0 0 24 24"><path d="m21 16-9 5-9-5"></path><path d="m21 12-9 5-9-5"></path><path d="m12 3 9 5-9 5-9-5 9-5z"></path></svg>
                    <span>Internet Packages</span>
                </a>
                <a class="{{ request()->routeIs('isp.customers.*') ? 'active' : '' }}" href="{{ route('isp.customers.index') }}">
                    <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path></svg>
                    <span>Customers</span>
                </a>
                <a class="{{ request()->routeIs('isp.routers.*') ? 'active' : '' }}" href="{{ route('isp.routers.index') }}">
                    <svg viewBox="0 0 24 24"><rect x="3" y="13" width="18" height="8" rx="2"></rect><path d="M17 17h.01"></path><path d="M7 17h.01"></path><path d="M12 13V9"></path><path d="M8.5 9.5a5 5 0 0 1 7 0"></path><path d="M5 6a10 10 0 0 1 14 0"></path></svg>
                    <span>MikroTik Routers</span>
                </a>
                <a class="{{ request()->routeIs('isp.provisioning.*') ? 'active' : '' }}" href="{{ route('isp.provisioning.index') }}">
                    <svg viewBox="0 0 24 24"><path d="M4 4h16v6H4z"></path><path d="M4 14h16v6H4z"></path><path d="M8 8h.01"></path><path d="M8 18h.01"></path><path d="M12 8h8"></path><path d="M12 18h8"></path></svg>
                    <span>Provisioning</span>
                </a>
            </div>
        </div>
        @if($isPlatform)
            <a class="{{ request()->routeIs('isps.*') ? 'active' : '' }}" href="{{ route('isps.index') }}">
                <svg viewBox="0 0 24 24"><path d="M3 21h18"></path><path d="M5 21V7l8-4v18"></path><path d="M19 21V11l-6-4"></path></svg>
                <span>ISPs</span>
            </a>
        @endif
        @if(Route::has('sales-invoices.index'))
            <a class="{{ request()->routeIs('sales-invoices.*') ? 'active' : '' }}" href="{{ route('sales-invoices.index') }}">
                <svg viewBox="0 0 24 24"><path d="M4 2h16v20l-4-2-4 2-4-2-4 2V2z"></path><path d="M8 7h8"></path><path d="M8 11h8"></path></svg>
                <span>Sales Invoice</span>
            </a>
        @endif
        @if(Route::has('purchase-invoices.index'))
            <a class="{{ request()->routeIs('purchase-invoices.*') ? 'active' : '' }}" href="{{ route('purchase-invoices.index') }}">
                <svg viewBox="0 0 24 24"><circle cx="8" cy="21" r="1"></circle><circle cx="19" cy="21" r="1"></circle><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path></svg>
                <span>Purchase</span>
            </a>
        @endif
        @if(Route::has('media-library'))
            <a class="{{ request()->routeIs('media-library') ? 'active' : '' }}" href="{{ route('media-library') }}">
                <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="9" cy="9" r="2"></circle><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"></path></svg>
                <span>Media Library</span>
            </a>
        @endif
    </nav>
</aside>
