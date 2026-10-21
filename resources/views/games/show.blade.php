@extends('layouts.app')
@section('title', $game->name)
@section('content')
    <p><a href="{{ route('games.index') }}">← All games</a></p>
    <div class="row" style="justify-content:space-between;align-items:flex-start">
        <div>
            <h1>{{ $game->name }}</h1>
            <div class="row">
                @if($game->is_featured)<span class="badge">Featured</span>@endif
                @if($game->genre)<span class="badge muted">{{ $game->genre }}</span>@endif
                @foreach($game->tags ?? [] as $tag)<span class="badge muted">{{ $tag }}</span>@endforeach
            </div>
        </div>
        <div class="row">
            @auth
                <form method="post" action="{{ route('games.favourite', $game) }}">@csrf
                    <button class="btn ghost" type="submit">{{ $isFavourite ? '★ Favourited' : '☆ Favourite' }} ({{ $favouriteCount }})</button>
                </form>
            @else
                <a class="btn ghost" href="{{ route('login') }}">☆ Favourite ({{ $favouriteCount }})</a>
            @endauth
            @if($game->url)<a class="btn" href="{{ route('games.play', $game) }}">Play now →</a>@endif
        </div>
    </div>

    @if($game->thumbnailUrl())
        <div class="card" style="margin:1rem 0;max-width:640px"><span class="thumb"><img src="{{ $game->thumbnailUrl() }}" alt="{{ $game->name }}"></span></div>
    @endif
    <p>{{ $game->description }}</p>

    <div class="stats">
        <div class="stat"><div class="v">{{ number_format($playerCount) }}</div><div class="l">Players</div></div>
        <div class="stat"><div class="v">{{ number_format($favouriteCount) }}</div><div class="l">Favourites</div></div>
        @if($myStats)
            <div class="stat"><div class="v">{{ number_format($myStats->best_score ?? 0) }}</div><div class="l">Your best</div></div>
            <div class="stat"><div class="v">{{ $myRank ? '#'.$myRank : '—' }}</div><div class="l">Your rank</div></div>
            <div class="stat"><div class="v">{{ $myStats->plays }}</div><div class="l">Your plays</div></div>
        @endif
    </div>

    <h2>Top players <a class="muted" style="font-size:.85rem;font-weight:400" href="{{ route('games.leaderboard', $game) }}">full leaderboard →</a></h2>
    @include('games._leaderboard', ['rows' => $leaderboard])
@endsection
