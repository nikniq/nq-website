<nav class="chips" aria-label="Admin sections" style="margin-bottom:1rem">
    <a class="chip {{ request()->routeIs('admin.games.*') ? 'on' : '' }}" href="{{ route('admin.games.index') }}">Games</a>
    <a class="chip {{ request()->routeIs('admin.players.index') ? 'on' : '' }}" href="{{ route('admin.players.index') }}">Players</a>
    <a class="chip {{ request()->routeIs('admin.players.activity') ? 'on' : '' }}" href="{{ route('admin.players.activity') }}">Activity</a>
</nav>
