@extends('layouts.app')
@section('title', 'My dashboard')
@section('content')
    <h1>Hi, {{ auth()->user()->name }}</h1>
    <p class="sub">Your scores and activity from every game on the portal, in one place.</p>

    <div class="stats">
        <div class="stat"><div class="v">{{ $summary['games'] }}</div><div class="l">Games played</div></div>
        <div class="stat"><div class="v">{{ number_format($summary['plays']) }}</div><div class="l">Plays</div></div>
        <div class="stat"><div class="v">{{ number_format($summary['wins']) }}</div><div class="l">Wins</div></div>
        <div class="stat"><div class="v">{{ number_format($summary['total_score']) }}</div><div class="l">Total score</div></div>
        <div class="stat"><div class="v">{{ \Carbon\CarbonInterval::seconds($summary['total_duration_seconds'])->cascade()->forHumans(['short' => true, 'parts' => 2]) ?: '0s' }}</div><div class="l">Time played</div></div>
    </div>

    <h2>Favourites</h2>
    @if($favourites->isEmpty())
        <p class="muted">No favourites yet. Tap ☆ on any game in the <a href="{{ route('games.index') }}">catalogue</a>.</p>
    @else
        <div class="grid">
            @foreach($favourites as $game)
                @include('games._card', ['game' => $game, 'favouriteIds' => $favourites->pluck('id')->all()])
            @endforeach
        </div>
    @endif

    <h2>Your games</h2>
    @if($gameStats->isEmpty())
        <p class="muted">Play a game that is signed in with your NikniQ account and its scores will show up here.</p>
    @else
        <table>
            <thead><tr><th>Game</th><th class="num">Best</th><th class="num">Rank</th><th class="num">Plays</th><th class="num">Wins</th><th>Last level</th><th>Last played</th></tr></thead>
            <tbody>
            @foreach($gameStats as $s)
                <tr>
                    <td><a href="{{ route('games.show', $s->game) }}">{{ $s->game->name }}</a></td>
                    <td class="num">{{ $s->best_score !== null ? number_format($s->best_score) : '—' }}</td>
                    <td class="num">{{ $ranks[$s->game_id] ? '#'.$ranks[$s->game_id] : '—' }}</td>
                    <td class="num">{{ $s->plays }}</td>
                    <td class="num">{{ $s->wins }}</td>
                    <td>{{ $s->last_level ?? '—' }}</td>
                    <td class="muted">{{ $s->last_played_at?->diffForHumans() ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <h2>Recent activity</h2>
    @if($activity->isEmpty())
        <p class="muted">Nothing yet.</p>
    @else
        <table>
            <thead><tr><th>When</th><th>Game</th><th>Event</th><th class="num">Score</th><th>Level</th><th>Result</th></tr></thead>
            <tbody>
            @foreach($activity as $e)
                <tr>
                    <td class="muted" title="{{ $e->occurred_at }}">{{ $e->occurred_at->diffForHumans() }}</td>
                    <td>{{ $e->game?->name ?? '—' }}</td>
                    <td><code>{{ $e->event }}</code></td>
                    <td class="num">{{ $e->score !== null ? number_format($e->score) : '' }}</td>
                    <td>{{ $e->level }}</td>
                    <td>@if($e->won === true)<span style="color:var(--ok)">Won</span>@elseif($e->won === false)<span class="muted">Lost</span>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
@endsection
