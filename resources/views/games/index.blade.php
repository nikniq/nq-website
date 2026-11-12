@extends('layouts.app')
@section('title', $query !== '' ? __('Search: :query', ['query' => $query]) : ($filters['genre'] !== '' ? __(':genre games', ['genre' => $filters['genre']]) : ($filters['dementia'] ? __('Dementia-friendly games') : __('Retro & Arcade games'))))
@section('description', __('Play :count free browser games on :site: arcade classics, puzzles, strategy and dementia-friendly picks. One account keeps your favourites and scores across every game.', ['count' => \App\Models\Game::enabled()->count(), 'site' => config('portal.name')]))
@section('jsonld')
<script type="application/ld+json">{!! json_encode([
    '@@context' => 'https://schema.org',
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
        <h1>{{ __('Retro & Arcade games') }}</h1>
        <p class="sub">{{ __(':count free games that run in your browser.', ['count' => \App\Models\Game::enabled()->count()]) }} @guest{!! __(':link to keep your favourites and scores.', ['link' => '<a href="'.e(route('register')).'">'.e(__('Create a free account')).'</a>']) !!}@else {{ __('Pick up where you left off, or try something new.') }}@endguest</p>
    </div>

    @if($spotlight)
        <section class="hero" aria-label="{{ __('Featured game') }}">
            <a class="hero-art" href="{{ route('games.show', $spotlight) }}" tabindex="-1" aria-hidden="true">
                @if($spotlight->thumbnailUrl())@include('games._picture', ['game' => $spotlight, 'eager' => true])@endif
            </a>
            <div class="hero-copy">
                <span class="eyebrow">★ {{ __('Featured game') }}</span>
                <h2><a href="{{ route('games.show', $spotlight) }}">{{ $spotlight->name }}</a></h2>
                <p>{{ $spotlight->description }}</p>
                <div class="row">
                    @if($spotlight->url)<a class="btn big" href="{{ route('games.play', $spotlight) }}">▶&nbsp; {{ __('Play now') }}</a>@endif
                    <a class="btn big ghost" href="{{ route('games.show', $spotlight) }}">{{ __('About the game') }}</a>
                </div>
            </div>
        </section>
        @php($latestNews = \App\Support\News::all()->first())
        @if($latestNews)
            <a class="news-strip" href="{{ route('news.show', $latestNews->slug) }}">
                <span class="tag">{{ __('New') }}</span>
                <span class="news-strip-title">{{ $latestNews->title }}</span>
                <span class="news-strip-more" aria-hidden="true">{{ __('Read') }} →</span>
            </a>
        @endif
    @endif

    <div class="finder">
        <form class="searchbar" method="get" action="{{ route('games.index') }}" role="search">
            <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" name="q" value="{{ $query }}" placeholder="{{ __('Search games') }}" aria-label="{{ __('Search games') }}">
            @foreach(['genre', 'featured', 'dementia', 'favourites', 'sort'] as $keep)
                @if(!empty($filters[$keep]))<input type="hidden" name="{{ $keep }}" value="{{ $filters[$keep] === true ? 1 : $filters[$keep] }}">@endif
            @endforeach
            <kbd class="hide-sm" aria-hidden="true">/</kbd>
            <button class="btn" type="submit">{{ __('Search') }}</button>
        </form>

        <nav class="filters" aria-label="{{ __('Filter games') }}">
            <div class="chips genres">
                <a class="chip {{ $filters['genre'] === '' ? 'on' : '' }}" @if($filters['genre'] === '') aria-current="true" @endif href="{{ $link(['genre' => '']) }}">{{ __('All') }}</a>
                @foreach($genres as $genre)
                    <a class="chip {{ $filters['genre'] === $genre ? 'on' : '' }}" @if($filters['genre'] === $genre) aria-current="true" @endif href="{{ $link(['genre' => $filters['genre'] === $genre ? '' : $genre]) }}">{{ $genre }}</a>
                @endforeach
            </div>
            <div class="filter-row">
                <div class="chips">
                    <a class="chip pink {{ $filters['featured'] ? 'on' : '' }}" @if($filters['featured']) aria-current="true" @endif href="{{ $link(['featured' => $filters['featured'] ? '' : 1]) }}">★ {{ __('Featured') }}</a>
                    <a class="chip calm {{ $filters['dementia'] ? 'on' : '' }}" @if($filters['dementia']) aria-current="true" @endif href="{{ $link(['dementia' => $filters['dementia'] ? '' : 1]) }}" title="{{ __('Calm pace, simple rules, no time pressure') }}">☺ {{ __('Dementia-friendly') }}</a>
                    @auth<a class="chip gold {{ $filters['favourites'] ? 'on' : '' }}" @if($filters['favourites']) aria-current="true" @endif href="{{ $link(['favourites' => $filters['favourites'] ? '' : 1]) }}">♥ {{ __('My favourites') }}</a>@endauth
                </div>
                <form class="sort" method="get" action="{{ route('games.index') }}">
                    @foreach(['q', 'genre', 'featured', 'dementia', 'favourites'] as $keep)
                        @if(!empty($filters[$keep]))<input type="hidden" name="{{ $keep }}" value="{{ $filters[$keep] === true ? 1 : $filters[$keep] }}">@endif
                    @endforeach
                    <label for="sort">{{ __('Sort by') }}</label>
                    <select id="sort" name="sort" onchange="this.form.submit()">
                        @foreach(['' => __('Featured first'), 'popular' => __('Most favourited'), 'name' => __('A to Z'), 'newest' => __('Newest')] as $key => $label)
                            <option value="{{ $key }}" @selected($filters['sort'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <noscript><button class="btn sm" type="submit">{{ __('Sort') }}</button></noscript>
                </form>
            </div>
        </nav>
    </div>

    @if($recent->isNotEmpty())
        <section aria-labelledby="recent-h" class="shelf">
            <h2 id="recent-h" class="section-title">{{ __('Continue playing') }}</h2>
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
            <p><strong>{{ $query !== '' ? __('No games match “:query”.', ['query' => $query]) : __('No games match these filters.') }}</strong></p>
            @php($all = \App\Models\Game::enabled()->get())
            @php($needle = \Illuminate\Support\Str::lower($query))
            @php($close = $query !== '' ? $all->map(fn ($g) => [$g, levenshtein($needle, \Illuminate\Support\Str::lower($g->name))])->filter(fn ($p) => $p[1] <= max(2, intdiv(strlen($needle), 3)))->sortBy(fn ($p) => $p[1])->first() : null)
            @if($close)<p>{!! __('Did you mean :link?', ['link' => '<a href="'.e(route('games.show', $close[0])).'"><strong>'.e($close[0]->name).'</strong></a>']) !!}</p>@endif
            <a class="btn" href="{{ route('games.index') }}">{{ __('Show all games') }}</a>
            <a class="btn ghost" href="{{ route('games.random') }}">🎲 {{ __('Surprise me') }}</a>
        </div>
        @php($picks = $all->where('is_featured', true)->take(4))
        @if($picks->isNotEmpty())
            <h2 class="section-title" style="margin-top:2rem">{{ __('Popular picks') }}</h2>
            <div class="grid">@foreach($picks as $game)@include('games._card', ['game' => $game])@endforeach</div>
        @endif
    @else
        <div class="section-head">
            <h2 class="section-title">{{ $query !== '' ? __('Results') : ($filters['genre'] !== '' ? __(':genre games', ['genre' => $filters['genre']]) : ($filters['dementia'] ? __('Dementia-friendly games') : __('All games'))) }}</h2>
            <span class="count">{{ trans_choice('{0} :count games|{1} :count game|[2,*] :count games', $games->count()) }}</span>
            <a class="clear" href="{{ route('games.random', $filters['dementia'] ? ['dementia' => 1] : []) }}" title="{{ __('Open a random game') }}">🎲 {{ __('Surprise me') }}</a>
            @if($query !== '' || $activeFilters)<a class="clear" href="{{ route('games.index') }}">{{ __('Clear filters') }} ✕</a>@endif
        </div>
        @if($filters['dementia'])<p class="muted" style="margin:-.4rem 0 1rem">{{ __('Calm pace, simple rules and no time pressure, suitable for players living with dementia.') }}</p>@endif
        <p class="muted live-empty" hidden>{{ __('No games match that search. Press Enter to search descriptions too.') }}</p>
        <div class="grid" data-live>
            @foreach($games as $game)
                @include('games._card', ['game' => $game, 'eager' => $loop->index < 4 && $recent->isEmpty()])
            @endforeach
        </div>
    @endif
@endsection
