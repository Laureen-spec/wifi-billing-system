<header class="topbar">
    <div>
        <strong>@yield('page_heading', 'StudyRoom Connect')</strong>
        <div class="muted">Powered by StudyRoom Connect</div>
    </div>
    <div class="top-user">
        <span class="avatar">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</span>
        <span class="user-meta">
            <strong>{{ auth()->user()->name ?? 'User' }}</strong>
            <span>{{ auth()->user()->type ?? 'user' }}</span>
        </span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="logout" type="submit">Logout</button>
        </form>
    </div>
</header>
