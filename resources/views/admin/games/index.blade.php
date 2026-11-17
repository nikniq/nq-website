@extends('layouts.app')
@section('title', 'Admin · Games')
@section('content')
    @include('admin._nav')
    <div class="row" style="justify-content:space-between"><h1>Games</h1><a class="btn" href="{{ route('admin.games.create') }}">+ New game</a></div>
    <div class="stats">
        <div class="stat"><div class="v">{{ $games->count() }}</div><div class="l">Games</div></div>
        <div class="stat"><div class="v">{{ number_format($totals['users']) }}</div><div class="l">Players</div></div>
        <div class="stat"><div class="v">{{ number_format($totals['events']) }}</div><div class="l">Events</div></div>
        <div class="stat"><div class="v">{{ number_format($totals['events_today']) }}</div><div class="l">Events today</div></div>
    </div>
    <div class="admin-toolbar">
        <input type="search" id="game-filter" placeholder="Filter by name, slug or genre" aria-label="Filter games" autocomplete="off">
        <div class="chips" role="group" aria-label="Status">
            <button type="button" class="chip on" data-status="">All <span class="n">{{ $games->count() }}</span></button>
            <button type="button" class="chip" data-status="enabled">Enabled <span class="n">{{ $games->where('is_enabled', true)->count() }}</span></button>
            <button type="button" class="chip" data-status="disabled">Disabled <span class="n">{{ $games->where('is_enabled', false)->count() }}</span></button>
        </div>
    </div>
    <div class="table-scroll"><table class="admin-games">
        <thead><tr><th>Game</th><th class="hide-sm">Slug</th><th class="hide-sm">Client</th><th class="num">★</th><th class="num">Players</th><th class="num">Events</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach($games as $g)
            <tr data-status="{{ $g->is_enabled ? 'enabled' : 'disabled' }}" data-text="{{ Str::lower($g->name.' '.$g->slug.' '.$g->genre) }}" @class(['is-off' => ! $g->is_enabled])>
                <td><a class="row-title" href="{{ route('admin.games.edit', $g) }}">{{ $g->name }}</a> @if($g->genre)<span class="muted genre">{{ $g->genre }}</span>@endif @if($g->is_featured)<span class="badge">Featured</span>@endif @if($g->is_dementia)<span class="badge calm">Dementia</span>@endif</td>
                <td class="hide-sm"><code style="white-space:nowrap">{{ $g->slug }}</code></td>
                <td class="hide-sm">{{ $g->is_public ? 'public (PKCE)' : 'confidential' }}</td>
                <td class="num">{{ $g->favourited_by_count }}</td>
                <td class="num">{{ $g->stats_count }}</td>
                <td class="num">{{ $g->events_count }}</td>
                <td><span class="status {{ $g->is_enabled ? 'on' : 'off' }}">{{ $g->is_enabled ? 'Enabled' : 'Disabled' }}</span></td>
                <td class="actions"><a class="btn sm" href="{{ route('admin.games.edit', $g) }}">Edit</a> <a class="btn sm ghost" href="{{ route('games.show', $g) }}" target="_blank" rel="noopener" aria-label="View {{ $g->name }} public page">View ↗</a></td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    <p class="muted" id="game-filter-empty" hidden>No games match.</p>
    <script>
    (function () {
        var input = document.getElementById('game-filter'), status = '', rows = document.querySelectorAll('.admin-games tbody tr'), empty = document.getElementById('game-filter-empty');
        function apply() {
            var q = input.value.trim().toLowerCase(), shown = 0;
            rows.forEach(function (r) { var hit = (!q || r.dataset.text.indexOf(q) !== -1) && (!status || r.dataset.status === status); r.hidden = !hit; if (hit) shown++; });
            empty.hidden = shown > 0;
        }
        input.addEventListener('input', apply);
        document.querySelectorAll('[data-status]').forEach(function (b) { if (b.tagName !== 'BUTTON') return; b.addEventListener('click', function () {
            status = b.dataset.status; document.querySelectorAll('button[data-status]').forEach(function (x) { x.classList.toggle('on', x === b); x.setAttribute('aria-pressed', x === b); }); apply(); }); });
    })();
    </script>
@endsection
