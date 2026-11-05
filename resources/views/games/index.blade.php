@extends('layouts.app')
@section('title', $query !== '' ? 'Search: '.$query : ($filters['genre'] !== '' ? $filters['genre'].' games' : ($filters['dementia'] ? 'Dementia-friendly games' : 'Retro &amp; Arcade games')))
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
    @php($spotlight = ($query === '' && ! $activeFilters && $filters['sort'] === '') ? $games->firstWhere('is_featured', true) : null)
    @php($link = fn (array $changes) => route('games.index', array_filter(array_merge(request()->query(), $changes), fn ($v) => $v !== '' && $v !== null && $v !== false && $v !== 0)))

    <div class="page-head">
        <h1>Retro &amp; Arcade games</h1>
        <p class="sub">{{ $games->count() }} free games that run in your browser. @guest<a href="{{ route('register') }}">Create a free account</a> to keep your favourites and scores.@else Pick up where you left off, or try something new.@endguest</p>
    </div>

    @if($spotlight)
        <section class="hero" aria-label="Featured game">
            <a class="hero-art" href="{{ route('games.show', $spotlight) }}" tabindex="-1" aria-hidden="true">
                @if($spotlight->thumbnailUrl())@include('games._picture', ['game' => $spotlight, 'eager' => true])@endif
            </a>
            <div class="hero-copy">
                <span class="eyebrow">★ Featured game</span>
                <h2><a href="{{ route('games.show', $spotlight) }}">{{ $spotlight->name }}</a></h2>
                <p>{{ $spotlight->description }}</p>
                <div class="row">
                    @if($spotlight->url)<a class="btn big" href="{{ route('games.play', $spotlight) }}">▶&nbsp; Play now</a>@endif
                    <a class="btn big ghost" href="{{ route('games.show', $spotlight) }}">About the game</a>
                </div>
            </div>
        </section>
    @endif

    <div class="finder">
        <form class="searchbar" method="get" action="{{ route('games.index') }}" role="search">
            <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" name="q" value="{{ $query }}" placeholder="Search games" aria-label="Search games">
            @foreach(['genre', 'featured', 'dementia', 'favourites', 'sort'] as $keep)
                @if(!empty($filters[$keep]))<input type="hidden" name="{{ $keep }}" value="{{ $filters[$keep] === true ? 1 : $filters[$keep] }}">@endif
            @endforeach
            <kbd class="hide-sm" aria-hidden="true">/</kbd>
            <button class="btn" type="submit">Search</button>
        </form>

        <nav class="filters" aria-label="Filter games">
            <div class="chips genres">
                <a class="chip {{ $filters['genre'] === '' ? 'on' : '' }}" @if($filters['genre'] === '') aria-current="true" @endif href="{{ $link(['genre' => '']) }}">All</a>
                @foreach($genres as $genre)
                    <a class="chip {{ $filters['genre'] === $genre ? 'on' : '' }}" @if($filters['genre'] === $genre) aria-current="true" @endif href="{{ $link(['genre' => $filters['genre'] === $genre ? '' : $genre]) }}">{{ $genre }}</a>
                @endforeach
            </div>
            <div class="filter-row">
                <div class="chips">
                    <a class="chip pink {{ $filters['featured'] ? 'on' : '' }}" @if($filters['featured']) aria-current="true" @endif href="{{ $link(['featured' => $filters['featured'] ? '' : 1]) }}">★ Featured</a>
                    <a class="chip calm {{ $filters['dementia'] ? 'on' : '' }}" @if($filters['dementia']) aria-current="true" @endif href="{{ $link(['dementia' => $filters['dementia'] ? '' : 1]) }}" title="Calm pace, simple rules, no time pressure">☺ Dementia-friendly</a>
                    @auth<a class="chip gold {{ $filters['favourites'] ? 'on' : '' }}" @if($filters['favourites']) aria-current="true" @endif href="{{ $link(['favourites' => $filters['favourites'] ? '' : 1]) }}">♥ My favourites</a>@endauth
                </div>
                <form class="sort" method="get" action="{{ route('games.index') }}">
                    @foreach(['q', 'genre', 'featured', 'dementia', 'favourites'] as $keep)
                        @if(!empty($filters[$keep]))<input type="hidden" name="{{ $keep }}" value="{{ $filters[$keep] === true ? 1 : $filters[$keep] }}">@endif
                    @endforeach
                    <label for="sort">Sort by</label>
                    <select id="sort" name="sort" onchange="this.form.submit()">
                        @foreach(['' => 'Featured first', 'popular' => 'Most favourited', 'name' => 'A to Z', 'newest' => 'Newest'] as $key => $label)
                            <option value="{{ $key }}" @selected($filters['sort'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <noscript><button class="btn sm" type="submit">Sort</button></noscript>
                </form>
            </div>
        </nav>
    </div>

    @if($recent->isNotEmpty())
        <section aria-labelledby="recent-h" class="shelf">
            <h2 id="recent-h" class="section-title">Continue playing</h2>
            <div class="grid">
                @foreach($recent as $game)
                    @include('games._card', ['game' => $game])
                @endforeach
            </div>
        </section>
    @endif

    @if($games->isEmpty())
        <div class="empty">
            <p class="empty-icon" aria-hidden="true">🕹️</p>
            <p><strong>No games match {{ $query !== '' ? '“'.$query.'”' : 'these filters' }}.</strong></p>
            <a class="btn" href="{{ route('games.index') }}">Show all games</a>
        </div>
    @else
        <div class="section-head">
            <h2 class="section-title">{{ $query !== '' ? 'Results' : ($filters['genre'] !== '' ? $filters['genre'].' games' : ($filters['dementia'] ? 'Dementia-friendly games' : 'All games')) }}</h2>
            <span class="count">{{ $games->count() }} {{ \Illuminate\Support\Str::plural('game', $games->count()) }}</span>
            @if($query !== '' || $activeFilters)<a class="clear" href="{{ route('games.index') }}">Clear filters ✕</a>@endif
        </div>
        @if($filters['dementia'])<p class="muted" style="margin:-.4rem 0 1rem">Calm pace, simple rules and no time pressure, suitable for players living with dementia.</p>@endif
        <div class="grid">
            @foreach($games as $game)
                @include('games._card', ['game' => $game, 'eager' => $loop->index < 4 && $recent->isEmpty()])
            @endforeach
        </div>
    @endif
@endsection
