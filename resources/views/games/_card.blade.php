@php($fav = in_array($game->id, $favouriteIds ?? [], true))
<article class="card {{ $game->is_featured ? 'featured' : '' }}">
    <a class="thumb" href="{{ route('games.show', $game) }}" aria-label="{{ $game->name }}">
        @if($game->thumbnailUrl())
            <img src="{{ $game->thumbnailUrl() }}" alt="" width="640" height="360" loading="lazy" decoding="async">
        @else
            <span class="thumb-fallback">🎮</span>
        @endif
        <span class="thumb-shade"></span>
        <span class="thumb-title">
            <strong>{{ $game->name }}</strong>
            @if($game->genre)<em>{{ $game->genre }}</em>@endif
        </span>
        @if($game->is_featured)<span class="ribbon">Featured</span>@endif
        @if($game->is_dementia)<span class="tag calm" title="Calm pace, simple rules, no time pressure">☺ Dementia-friendly</span>@endif
    </a>
    @auth
        <form class="fav-form" method="post" action="{{ route('games.favourite', $game) }}" data-fav data-name="{{ $game->name }}">@csrf
            <button class="fav {{ $fav ? 'on' : '' }}" type="submit" aria-label="{{ $fav ? 'Remove '.$game->name.' from favourites' : 'Add '.$game->name.' to favourites' }}" aria-pressed="{{ $fav ? 'true' : 'false' }}"><span data-icon aria-hidden="true">{{ $fav ? '★' : '☆' }}</span></button>
        </form>
    @endauth
    <div class="body">
        <p>{{ \Illuminate\Support\Str::limit($game->description, 110) }}</p>
        <div class="row actions">
            @if($game->url)<a class="btn sm" href="{{ route('games.play', $game) }}" aria-label="Play {{ $game->name }}">▶ Play</a>@endif
            <a class="btn sm ghost" href="{{ route('games.leaderboard', $game) }}" aria-label="{{ $game->name }} leaderboard">Leaderboard</a>
            @if(isset($game->favourited_by_count) && $game->favourited_by_count)<span class="muted fav-count" title="Favourited by {{ $game->favourited_by_count }} players">★ {{ $game->favourited_by_count }}</span>@endif
        </div>
    </div>
</article>
