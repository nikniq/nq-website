@extends('layouts.app')
@section('title', $game->name)
@section('description', \Illuminate\Support\Str::limit($game->description ?: 'Play '.$game->name.' free in your browser on '.config('portal.name').'.', 155))
@section('og_type', 'video.other')
@if($game->thumbnailUrl())@section('image', url($game->thumbnailUrl()))@endif
@section('jsonld')
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        ['@type' => 'VideoGame', 'name' => $game->name, 'description' => $game->description, 'url' => route('games.show', $game), 'image' => $game->thumbnailUrl() ? url($game->thumbnailUrl()) : null, 'genre' => $game->genre, 'gamePlatform' => 'Web browser', 'applicationCategory' => 'Game', 'operatingSystem' => 'Any', 'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'], 'publisher' => ['@type' => 'Organization', 'name' => config('portal.name'), 'url' => url('/')]],
        ['@type' => 'BreadcrumbList', 'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Games', 'item' => route('games.index')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $game->name, 'item' => route('games.show', $game)],
        ]],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection
@section('content')
    <p><a href="{{ route('games.index') }}">← All games</a></p>
    <div class="game-hero">
        <a class="game-art" href="{{ $game->url ? route('games.play', $game) : '#' }}" aria-label="Play {{ $game->name }}">
            @if($game->thumbnailUrl())<img src="{{ $game->thumbnailUrl() }}" alt="{{ $game->name }} screenshot" width="640" height="360">@else<span class="thumb-fallback">🎮</span>@endif
        </a>
        <div class="game-info">
            <h1>{{ $game->name }}</h1>
            <div class="row">
                @if($game->is_featured)<span class="badge">Featured</span>@endif
                @if($game->genre)<a class="badge muted" href="{{ route('games.index', ['genre' => $game->genre]) }}">{{ $game->genre }}</a>@endif
                @if($game->is_dementia)<a class="badge calm" href="{{ route('games.index', ['dementia' => 1]) }}">☺ Dementia-friendly</a>@endif
            </div>
            <p class="lead">{{ $game->description }}</p>
            @if($game->is_dementia)<p class="muted" style="font-size:.9rem;margin:0">Dementia-friendly: calm pace, simple rules, no time pressure.</p>@endif
            <div class="row">
                @if($game->url)<a class="btn big" href="{{ route('games.play', $game) }}">▶ Play now</a>@endif
                @auth
                    <form method="post" action="{{ route('games.favourite', $game) }}" data-fav data-name="{{ $game->name }}">@csrf
                        <button class="btn ghost big {{ $isFavourite ? 'on' : '' }}" type="submit" aria-pressed="{{ $isFavourite ? 'true' : 'false' }}"><span data-icon aria-hidden="true">{{ $isFavourite ? '★' : '☆' }}</span>&nbsp;<span data-label>{{ $isFavourite ? 'Favourited' : 'Favourite' }}</span></button>
                    </form>
                @else
                    <a class="btn ghost big" href="{{ route('login') }}">☆ Log in to favourite</a>
                @endauth
            </div>
            @php($shareText = 'Play '.$game->name.' free on '.config('portal.name'))
            <div class="share">
                <button type="button" class="btn sm ghost" data-share data-title="{{ $game->name }}" data-text="{{ $shareText }}" data-url="{{ route('games.show', $game) }}">Share</button>
                <a class="btn sm ghost" href="https://wa.me/?text={{ urlencode($shareText.' '.route('games.show', $game)) }}" target="_blank" rel="noopener">WhatsApp</a>
                <a class="btn sm ghost" href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('games.show', $game)) }}" target="_blank" rel="noopener">Facebook</a>
                <a class="btn sm ghost" href="mailto:?subject={{ rawurlencode($game->name.' on '.config('portal.name')) }}&body={{ rawurlencode($shareText.': '.route('games.show', $game)) }}">Email</a>
            </div>
            <p class="muted" style="font-size:.85rem;margin:0">Opens {{ parse_url($game->url ?? '', PHP_URL_HOST) ?: 'the game' }}. Your scores are saved when you sign in there with your NikniQ account.</p>
        </div>
    </div>

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

    @if($related->isNotEmpty())
        <h2>You might also like</h2>
        <div class="grid">
            @foreach($related as $other)
                @include('games._card', ['game' => $other, 'favouriteIds' => auth()->check() ? auth()->user()->favouriteGames()->pluck('games.id')->all() : []])
            @endforeach
        </div>
    @endif
@endsection
