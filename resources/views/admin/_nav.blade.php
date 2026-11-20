<nav class="chips" aria-label="Admin sections" style="margin-bottom:1rem">
    <a class="chip {{ request()->routeIs('admin.games.*') ? 'on' : '' }}" href="{{ route('admin.games.index') }}">Games</a>
    <a class="chip {{ request()->routeIs('admin.players.index', 'admin.players.show') ? 'on' : '' }}" href="{{ route('admin.players.index') }}">Players</a>
    <a class="chip {{ request()->routeIs('admin.players.activity') ? 'on' : '' }}" href="{{ route('admin.players.activity') }}">Activity</a>
    <a class="chip {{ request()->routeIs('admin.logs.*') ? 'on' : '' }}" href="{{ route('admin.logs.index') }}">Logs</a>
    <a class="chip {{ request()->routeIs('admin.reports.*') ? 'on' : '' }}" href="{{ route('admin.reports.index') }}">Reports</a>
    <a class="chip {{ request()->routeIs('admin.sales.*') ? 'on' : '' }}" href="{{ route('admin.sales.index') }}">Sales</a>
</nav>
