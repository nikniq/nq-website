@extends('layouts.app')
@section('title', $query !== '' ? 'Search: '.$query : ($filters['genre'] !== '' ? $filters['genre'].' games' : ($filters['dementia'] ? 'Dementia-friendly games' : 'Free browser games')))
@section('description', 'Play '.$games->count().' free browser games on '.config('portal.name').': arcade classics, puzzles, strategy and dementia-friendly picks. One account keeps your favourites and scores across every game.')
@section('jsonld')
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        ['@type' => 'WebSite', 'name' => config('portal.name'), 'url' => url('/'), 'potentialAction' => ['@type' => 'SearchAction', 'target' => url('/').'?q={search_term_string}', 'query-input' => 'required name=search_term_string']],
        ['@type' => 'ItemList', 'itemListElement' => $games->values()->map(fn ($g, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $g->name, 'url' => route('games.show', $g)])->all()],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection
@section('content')
    <h1>Free browser games</h1>
    <p class="sub">{{ config('portal.tagline') }} @guest<a href="{{ route('register') }}">Sign up</a> to save favourites and keep your scores across every game.@endguest</p>

    @php
        // Build a link that keeps the other filters and toggles one.
        $link = fn (array $changes) => route('games.index', array_filter(array_merge(request()->query(), $changes), fn ($v) => $v !== '' && $v !== null && $v !== false && $v !== 0));
    @endphp
    <form class="search" method="get" action="{{ route('games.index') }}">
        <input type="search" name="q" value="{{ $query }}" placeholder="Search games…" aria-label="Search games">
        @foreach(['genre', 'featured', 'dementia', 'favourites', 'sort'] as $keep)
            @if(!empty($filters[$keep]))<input type="hidden" name="{{ $keep }}" value="{{ $filters[$keep] === true ? 1 : $filters[$keep] }}">@endif
        @endforeach
        <button class="btn" type="submit">Search</button>
        @if($query !== '' || $activeFilters)<a class="btn ghost" href="{{ route('games.index') }}">Clear</a>@endif
    </form>

    <nav class="filters" aria-label="Filter games">
        <div class="chips genres">
            <a class="chip {{ $filters['genre'] === '' ? 'on' : '' }}" @if($filters['genre'] === '') aria-current="true" @endif href="{{ $link(['genre' => '']) }}">All genres</a>
            @foreach($genres as $genre)
                <a class="chip {{ $filters['genre'] === $genre ? 'on' : '' }}" @if($filters['genre'] === $genre) aria-current="true" @endif href="{{ $link(['genre' => $filters['genre'] === $genre ? '' : $genre]) }}">{{ $genre }}</a>
            @endforeach
        </div>
        <div class="chips">
            <a class="chip pink {{ $filters['featured'] ? 'on' : '' }}" @if($filters['featured']) aria-current="true" @endif href="{{ $link(['featured' => $filters['featured'] ? '' : 1]) }}">★ Featured</a>
            <a class="chip calm {{ $filters['dementia'] ? 'on' : '' }}" @if($filters['dementia']) aria-current="true" @endif href="{{ $link(['dementia' => $filters['dementia'] ? '' : 1]) }}" title="Calm pace, simple rules, no time pressure">☺ Dementia-friendly</a>
            @auth<a class="chip gold {{ $filters['favourites'] ? 'on' : '' }}" @if($filters['favourites']) aria-current="true" @endif href="{{ $link(['favourites' => $filters['favourites'] ? '' : 1]) }}">♥ My favourites</a>@endauth
            <span class="sort">
                Sort
                @foreach(['' => 'Featured first', 'popular' => 'Most favourited', 'name' => 'A to Z', 'newest' => 'Newest'] as $key => $label)
                    <a class="{{ $filters['sort'] === $key ? 'on' : '' }}" href="{{ $link(['sort' => $key]) }}">{{ $label }}</a>
                @endforeach
            </span>
        </div>
    </nav>

    @if($recent->isNotEmpty())
        <section aria-labelledby="recent-h" style="margin:0 0 1.4rem">
            <h2 id="recent-h" style="margin:.2rem 0 .6rem">Continue playing</h2>
            <div class="grid">
                @foreach($recent as $game)
                    @include('games._card', ['game' => $game])
                @endforeach
            </div>
        </section>
        <h2 style="margin:0 0 .6rem">All games</h2>
    @endif

    @if($games->isEmpty())
        <p class="muted">No games match {{ $query !== '' ? '“'.$query.'”' : 'these filters' }}. <a href="{{ route('games.index') }}">Show everything</a>.</p>
    @else
        <p class="muted" style="margin:.2rem 0 .8rem;font-size:.85rem">{{ $games->count() }} {{ \Illuminate\Support\Str::plural('game', $games->count()) }}@if($filters['dementia']) suitable for players living with dementia @endif</p>
        <div class="grid">
            @foreach($games as $game)
                @include('games._card', ['game' => $game])
            @endforeach
        </div>
    @endif
@endsection
