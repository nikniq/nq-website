@extends('layouts.app')
@section('title', $player->name)
@section('description', $player->name.' on '.config('portal.name').': '.$summary['games'].' games, '.$summary['wins'].' wins.')
@section('content')
    <p><a href="{{ route('hall') }}">← Hall of fame</a></p>
    <div class="row" style="gap:1rem;align-items:center">
        @if($player->avatar)<img src="{{ $player->avatar }}" alt="" style="width:64px;height:64px;border-radius:50%;object-fit:cover">@else<span style="width:64px;height:64px;border-radius:50%;background:var(--panel-2);display:inline-flex;align-items:center;justify-content:center;font-size:1.6rem">🎮</span>@endif
        <div>
            <h1 style="margin:0">{{ $player->name }}</h1>
            <p class="muted" style="margin:0">Playing since {{ $player->created_at->format('F Y') }}@if($isMe) · <a href="{{ route('profile') }}">edit profile</a>@endif</p>
        </div>
    </div>

    <div class="stats" style="margin-top:1rem">
        <div class="stat"><div class="v">{{ $summary['games'] }}</div><div class="l">Games played</div></div>
        <div class="stat"><div class="v">{{ number_format($summary['plays']) }}</div><div class="l">Plays</div></div>
        <div class="stat"><div class="v">{{ number_format($summary['wins']) }}</div><div class="l">Wins</div></div>
        <div class="stat"><div class="v">{{ number_format($summary['total_score']) }}</div><div class="l">Total score</div></div>
    </div>

    <h2>Best scores</h2>
    @if($gameStats->isEmpty())
        <p class="muted">No scores yet.</p>
    @else
        <div class="table-scroll"><table>
            <thead><tr><th>Game</th><th class="num">Best</th><th class="num">Rank</th><th class="num hide-sm">Plays</th><th class="num hide-sm">Wins</th><th class="hide-sm">Last played</th></tr></thead>
            <tbody>
            @foreach($gameStats as $s)
                <tr>
                    <td><a href="{{ route('games.show', $s->game) }}">{{ $s->game->name }}</a></td>
                    <td class="num">{{ $s->best_score !== null ? number_format($s->best_score) : '—' }}</td>
                    <td class="num">{{ isset($ranks[$s->game_id]) ? '#'.$ranks[$s->game_id] : '—' }}</td>
                    <td class="num hide-sm">{{ $s->plays }}</td>
                    <td class="num hide-sm">{{ $s->wins }}</td>
                    <td class="muted hide-sm">{{ $s->last_played_at?->diffForHumans() ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    @endif

    @if($favourites->isNotEmpty())
        <h2>Favourite games</h2>
        <div class="grid">
            @foreach($favourites as $game)
                @include('games._card', ['game' => $game, 'favouriteIds' => auth()->check() ? auth()->user()->favouriteGames()->pluck('games.id')->all() : []])
            @endforeach
        </div>
    @endif
@endsection
