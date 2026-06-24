<header class="topbar">
    <div>
        <strong>@yield('page_heading', 'StudyRoom TechLab Billing')</strong>
        <div class="muted">Powered by StudyRoom TechLab</div>
    </div>
    <div class="actions">
        <span class="muted">{{ auth()->user()->name ?? 'User' }}</span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn secondary" type="submit">Logout</button>
        </form>
    </div>
</header>
