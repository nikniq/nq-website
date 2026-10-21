@php($fav = in_array($game->id, $favouriteIds ?? [], true))
<div class="card">
    <a class="thumb" href="{{ route('games.show', $game) }}" @if($game->thumbnailUrl()) style="background-image:url('{{ $game->thumbnailUrl() }}')" @endif>
        @unless($game->thumbnailUrl())🎮@endunless
    </a>
    <div class="body">
        <div class="row" style="justify-content:space-between">
            <h3><a href="{{ route('games.show', $game) }}">{{ $game->name }}</a></h3>
            @auth
                <form method="post" action="{{ route('games.favourite', $game) }}">@csrf
                    <button class="fav {{ $fav ? 'on' : '' }}" type="submit" title="{{ $fav ? 'Remove from favourites' : 'Add to favourites' }}" style="background:none;border:0;cursor:pointer;color:inherit">{{ $fav ? '★' : '☆' }}</button>
                </form>
            @endauth
        </div>
        <div class="row">
            @if($game->is_featured)<span class="badge">Featured</span>@endif
            @if($game->genre)<span class="badge muted">{{ $game->genre }}</span>@endif
            @if(isset($game->favourited_by_count) && $game->favourited_by_count)<span class="muted" style="font-size:.8rem">★ {{ $game->favourited_by_count }}</span>@endif
        </div>
        <p>{{ \Illuminate\Support\Str::limit($game->description, 110) }}</p>
        <div class="row">
            @if($game->url)<a class="btn sm" href="{{ route('games.play', $game) }}">Play</a>@endif
            <a class="btn sm ghost" href="{{ route('games.leaderboard', $game) }}">Leaderboard</a>
        </div>
    </div>
</div>
