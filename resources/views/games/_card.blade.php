@php($fav = in_array($game->id, $favouriteIds ?? [], true))
<article class="card {{ $game->is_featured ? 'featured' : '' }}">
    <a class="thumb" href="{{ route('games.show', $game) }}" aria-label="{{ $game->name }}">
        @if($game->thumbnailUrl())
            <img src="{{ $game->thumbnailUrl() }}" alt="" loading="lazy" decoding="async">
        @else
            <span class="thumb-fallback">🎮</span>
        @endif
        <span class="thumb-shade"></span>
        <span class="thumb-title">
            <strong>{{ $game->name }}</strong>
            @if($game->genre)<em>{{ $game->genre }}</em>@endif
        </span>
        @if($game->is_featured)<span class="ribbon">Featured</span>@endif
    </a>
    @auth
        <form class="fav-form" method="post" action="{{ route('games.favourite', $game) }}">@csrf
            <button class="fav {{ $fav ? 'on' : '' }}" type="submit" title="{{ $fav ? 'Remove from favourites' : 'Add to favourites' }}" aria-pressed="{{ $fav ? 'true' : 'false' }}">{{ $fav ? '★' : '☆' }}</button>
        </form>
    @endauth
    <div class="body">
        <p>{{ \Illuminate\Support\Str::limit($game->description, 110) }}</p>
        <div class="row actions">
            @if($game->url)<a class="btn sm" href="{{ route('games.play', $game) }}">▶ Play</a>@endif
            <a class="btn sm ghost" href="{{ route('games.leaderboard', $game) }}">Leaderboard</a>
            @if(isset($game->favourited_by_count) && $game->favourited_by_count)<span class="muted fav-count" title="Favourited by {{ $game->favourited_by_count }} players">★ {{ $game->favourited_by_count }}</span>@endif
        </div>
    </div>
</article>
