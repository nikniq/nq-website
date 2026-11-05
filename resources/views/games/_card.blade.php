@php($fav = in_array($game->id, $favouriteIds ?? [], true))
<article class="card" data-search="{{ \Illuminate\Support\Str::lower($game->name.' '.$game->genre) }}">
    <a class="thumb" href="{{ route('games.show', $game) }}" tabindex="-1" aria-hidden="true">
        @if($game->thumbnailUrl())
            @include('games._picture', ['game' => $game, 'eager' => $eager ?? false])
        @else
            <span class="thumb-fallback">🎮</span>
        @endif
        <span class="pills">
            @if($game->is_featured)<span class="pill-tag hot">★ Featured</span>@endif
            @if($game->is_dementia)<span class="pill-tag calm" title="Calm pace, simple rules, no time pressure">☺ Calm</span>@endif
        </span>
    </a>
    @auth
        <form class="fav-form" method="post" action="{{ route('games.favourite', $game) }}" data-fav data-name="{{ $game->name }}">@csrf
            <button class="fav {{ $fav ? 'on' : '' }}" type="submit" aria-label="{{ $fav ? 'Remove '.$game->name.' from favourites' : 'Add '.$game->name.' to favourites' }}" aria-pressed="{{ $fav ? 'true' : 'false' }}"><span data-icon aria-hidden="true">{{ $fav ? '★' : '☆' }}</span></button>
        </form>
    @endauth
    <div class="body">
        <h3 class="card-title"><a href="{{ route('games.show', $game) }}">{{ $game->name }}</a></h3>
        <p class="card-meta">
            @if($game->genre)<span>{{ $game->genre }}</span>@endif
            @if(isset($game->favourited_by_count) && $game->favourited_by_count)<span title="Favourited by {{ $game->favourited_by_count }} players">★ {{ $game->favourited_by_count }}</span>@endif
        </p>
        <p class="card-desc">{{ $game->description }}</p>
        @if($game->url)<a class="btn play" href="{{ route('games.play', $game) }}" aria-label="Play {{ $game->name }}">▶&nbsp; Play</a>@endif
    </div>
</article>
