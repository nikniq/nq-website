@extends('layouts.app')
@section('title', __('My dashboard'))
@section('content')
    <h1>{{ __('Hi, :name', ['name' => auth()->user()->name]) }}</h1>
    <p class="sub">{{ __('Your scores and activity from every game on the portal, in one place.') }}</p>

    @if($summary['games'] === 0)
        <h2 style="margin-top:.4rem">{{ __('Get started') }}</h2>
        <ol class="steps">
            <li><strong>{{ __('Pick a game') }}</strong>{!! __('Browse the :link or try one of the picks below.', ['link' => '<a href="'.e(route('games.index')).'">'.e(__('catalogue')).'</a>']) !!}</li>
            <li><strong>{{ __('Sign in inside the game') }}</strong>{{ __('Choose “Sign in with NikniQ” in the game so your scores come back here.') }}</li>
            <li><strong>{{ __('Star your favourites') }}</strong>{{ __('Tap ☆ on any game to keep it at the top of your list.') }}</li>
        </ol>
        <h2>{{ __('Popular right now') }}</h2>
        <div class="grid" style="margin-bottom:1.2rem">
            @foreach($suggested as $game)
                @include('games._card', ['game' => $game, 'favouriteIds' => $favourites->pluck('id')->all()])
            @endforeach
        </div>
    @else
    <div class="stats">
        <div class="stat"><div class="v">{{ $summary['games'] }}</div><div class="l">{{ __('Games played') }}</div></div>
        <div class="stat"><div class="v">{{ number_format($summary['plays']) }}</div><div class="l">{{ __('Plays') }}</div></div>
        <div class="stat"><div class="v">{{ number_format($summary['wins']) }}</div><div class="l">{{ __('Wins') }}</div></div>
        <div class="stat"><div class="v">{{ number_format($summary['total_score']) }}</div><div class="l">{{ __('Total score') }}</div></div>
        <div class="stat"><div class="v">{{ \Carbon\CarbonInterval::seconds($summary['total_duration_seconds'])->cascade()->forHumans(['short' => true, 'parts' => 2]) ?: '0s' }}</div><div class="l">{{ __('Time played') }}</div></div>
    </div>
    @endif

    <h2>{{ __('Favourites') }}</h2>
    @if($favourites->isEmpty())
        <p class="muted">{{ __('No favourites yet.') }} {!! __('Tap ☆ on any game in the :link.', ['link' => '<a href="'.e(route('games.index')).'">'.e(__('catalogue')).'</a>']) !!}</p>
    @else
        <div class="grid">
            @foreach($favourites as $game)
                @include('games._card', ['game' => $game, 'favouriteIds' => $favourites->pluck('id')->all()])
            @endforeach
        </div>
    @endif

    @if($summary['games'] > 0 || $activity->isNotEmpty())
    <h2>{{ __('Your games') }}</h2>
    @if($gameStats->isEmpty())
        <p class="muted">{{ __('Play a game that is signed in with your NikniQ account and its scores will show up here.') }}</p>
    @else
        <table>
            <thead><tr><th>{{ __('Game') }}</th><th class="num">{{ __('Best') }}</th><th class="num">{{ __('Rank') }}</th><th class="num">{{ __('Plays') }}</th><th class="num">{{ __('Wins') }}</th><th>{{ __('Last level') }}</th><th>{{ __('Last played') }}</th></tr></thead>
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

    <h2>{{ __('Recent activity') }}</h2>
    @if($activity->isEmpty())
        <p class="muted">{{ __('Nothing yet.') }}</p>
    @else
        <table>
            <thead><tr><th>{{ __('When') }}</th><th>{{ __('Game') }}</th><th>{{ __('Event') }}</th><th class="num">{{ __('Score') }}</th><th>{{ __('Level') }}</th><th>{{ __('Result') }}</th></tr></thead>
            <tbody>
            @foreach($activity as $e)
                <tr>
                    <td class="muted" title="{{ $e->occurred_at }}">{{ $e->occurred_at->diffForHumans() }}</td>
                    <td>{{ $e->game?->name ?? '—' }}</td>
                    <td><code>{{ $e->event }}</code></td>
                    <td class="num">{{ $e->score !== null ? number_format($e->score) : '' }}</td>
                    <td>{{ $e->level }}</td>
                    <td>@if($e->won === true)<span style="color:var(--ok)">{{ __('Won') }}</span>@elseif($e->won === false)<span class="muted">{{ __('Lost') }}</span>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
    @endif
@endsection
