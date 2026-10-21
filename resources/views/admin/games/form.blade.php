@extends('layouts.app')
@section('title', $game->exists ? 'Edit '.$game->name : 'New game')
@section('content')
    <p><a href="{{ route('admin.games.index') }}">← Games</a></p>
    <h1>{{ $game->exists ? $game->name : 'New game' }}</h1>

    @if(session('client_secret'))
        <div class="flash">
            <strong>Client secret (shown once):</strong> <code>{{ session('client_secret') }}</code><br>
            <span class="muted">Store it in the game's server config now. It cannot be retrieved later, only rotated.</span>
        </div>
    @endif

    @if($game->exists)
        <div class="panel" style="margin-bottom:1rem">
            <h2 style="margin-top:0">SSO credentials</h2>
            <p>Client ID: <code>{{ $game->client_id }}</code></p>
            <p class="muted">Authorize URL: <code>{{ route('sso.authorize') }}</code> · Token URL: <code>{{ route('api.sso.token') }}</code> · Events URL: <code>{{ route('api.games.events.store', $game) }}</code></p>
            @unless($game->is_public)
                <form method="post" action="{{ route('admin.games.rotate-secret', $game) }}" onsubmit="return confirm('Rotate the secret? The game will stop authenticating until it is updated.')">@csrf
                    <button class="btn sm ghost" type="submit">Rotate secret</button>
                </form>
            @endunless
        </div>
    @endif

    <form class="stack" method="post" action="{{ $game->exists ? route('admin.games.update', $game) : route('admin.games.store') }}" style="max-width:680px">@csrf
        @if($game->exists)@method('PUT')@endif
        @if($errors->any())<div class="flash error">{{ $errors->first() }}</div>@endif
        <label>Name <input type="text" name="name" value="{{ old('name', $game->name) }}" required></label>
        <label>Slug (URL and API key, e.g. <code>chopper</code>) <input type="text" name="slug" value="{{ old('slug', $game->slug) }}" required pattern="[a-z0-9\-]+"></label>
        <label>Description <textarea name="description">{{ old('description', $game->description) }}</textarea></label>
        <label>Play URL <input type="url" name="url" value="{{ old('url', $game->url) }}" placeholder="https://chopper.nikniq.com"></label>
        <label>Thumbnail (URL or path under public_html, e.g. <code>/images/games/chopper.png</code>) <input type="text" name="thumbnail" value="{{ old('thumbnail', $game->thumbnail) }}"></label>
        <label>Genre <input type="text" name="genre" value="{{ old('genre', $game->genre) }}" placeholder="Arcade"></label>
        <label>Tags (comma separated) <input type="text" name="tags" value="{{ old('tags', implode(', ', $game->tags ?? [])) }}"></label>
        <label>Allowed redirect URIs (one per line; exact match) <textarea name="redirect_uris" placeholder="https://chopper.nikniq.com/auth/callback">{{ old('redirect_uris', implode("\n", $game->redirect_uris ?? [])) }}</textarea></label>
        <label>Sort order <input type="number" name="sort_order" value="{{ old('sort_order', $game->sort_order ?? 0) }}" min="0"></label>
        <label class="inline"><input type="hidden" name="is_public" value="0"><input type="checkbox" name="is_public" value="1" @checked(old('is_public', $game->is_public))> Public client (browser-only game, PKCE instead of a secret)</label>
        <label class="inline"><input type="hidden" name="is_enabled" value="0"><input type="checkbox" name="is_enabled" value="1" @checked(old('is_enabled', $game->is_enabled ?? true))> Enabled</label>
        <label class="inline"><input type="hidden" name="is_featured" value="0"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $game->is_featured))> Featured</label>
        <label>Admin notes <textarea name="notes">{{ old('notes', $game->notes) }}</textarea></label>
        <div class="row"><button class="btn" type="submit">{{ $game->exists ? 'Save' : 'Create game' }}</button></div>
    </form>

    @if($game->exists)
        <form method="post" action="{{ route('admin.games.destroy', $game) }}" style="margin-top:2rem" onsubmit="return confirm('Delete {{ $game->name }} and all of its favourites, tokens, events and stats?')">@csrf @method('DELETE')
            <button class="btn sm danger" type="submit">Delete game</button>
        </form>
    @endif
@endsection
