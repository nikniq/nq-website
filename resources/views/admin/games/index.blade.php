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
    <table>
        <thead><tr><th>Game</th><th>Slug</th><th>Client</th><th class="num">★</th><th class="num">Players</th><th class="num">Events</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach($games as $g)
            <tr>
                <td><a href="{{ route('admin.games.edit', $g) }}">{{ $g->name }}</a> @if($g->is_featured)<span class="badge">Featured</span>@endif @if($g->is_dementia)<span class="badge calm">Dementia</span>@endif</td>
                <td><code>{{ $g->slug }}</code></td>
                <td>{{ $g->is_public ? 'public (PKCE)' : 'confidential' }}</td>
                <td class="num">{{ $g->favourited_by_count }}</td>
                <td class="num">{{ $g->stats_count }}</td>
                <td class="num">{{ $g->events_count }}</td>
                <td>{!! $g->is_enabled ? '<span style="color:var(--ok)">enabled</span>' : '<span class="muted">disabled</span>' !!}</td>
                <td><a class="btn sm ghost" href="{{ route('games.show', $g) }}">View</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
