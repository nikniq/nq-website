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
            <dl class="kv">
                <dt>Client ID</dt><dd><code>{{ $game->client_id }}</code> <button type="button" class="btn sm ghost" data-copy="{{ $game->client_id }}">Copy</button></dd>
                <dt>Client type</dt><dd>{{ $game->is_public ? 'Public (PKCE, no secret)' : 'Confidential (client secret)' }}</dd>
                <dt>Discovery</dt><dd><code>{{ url('/.well-known/openid-configuration') }}</code></dd>
                <dt>Events URL</dt><dd><code>{{ route('api.games.events.store', $game) }}</code></dd>
            </dl>
            <p><a href="{{ route('developers', ['game' => $game->slug]) }}">Integration guide with this game's snippet →</a></p>
            @unless($game->is_public)
                <form method="post" action="{{ route('admin.games.rotate-secret', $game) }}" onsubmit="return confirm('Rotate the secret? The game will stop authenticating until it is updated.')">@csrf
                    <button class="btn sm ghost" type="submit">Rotate secret</button>
                </form>
            @endunless
        </div>
    @endif

    <form class="admin-form" method="post" action="{{ $game->exists ? route('admin.games.update', $game) : route('admin.games.store') }}">@csrf
        @if($game->exists)@method('PUT')@endif
        @if($errors->any())<div class="flash error">{{ $errors->first() }}</div>@endif

        <fieldset>
            <legend>Catalogue</legend>
            <div class="field-grid">
                <label>Name <input type="text" name="name" value="{{ old('name', $game->name) }}" required></label>
                <label>Slug <input type="text" name="slug" value="{{ old('slug', $game->slug) }}" required pattern="[a-z0-9\-]+"><small>Lowercase letters, numbers and dashes. Used in the URL and the API.</small></label>
                <label>Genre <input type="text" name="genre" value="{{ old('genre', $game->genre) }}" placeholder="Arcade"></label>
                <label>Sort order <input type="number" name="sort_order" value="{{ old('sort_order', $game->sort_order ?? 0) }}" min="0"><small>Lower numbers appear first.</small></label>
            </div>
            <label>Description <textarea name="description" rows="3">{{ old('description', $game->description) }}</textarea></label>
            <div class="field-grid">
                <label>Play URL <input type="url" name="url" value="{{ old('url', $game->url) }}" placeholder="https://chopper.nikniq.com"></label>
                <label>Thumbnail <input type="text" name="thumbnail" value="{{ old('thumbnail', $game->thumbnail) }}" placeholder="/images/games/chopper.jpg"><small>A URL, or a path under public_html.</small></label>
            </div>
            <label>Tags <input type="text" name="tags" value="{{ old('tags', implode(', ', $game->tags ?? [])) }}"><small>Comma separated.</small></label>
        </fieldset>

        <fieldset>
            <legend>Visibility</legend>
            <div class="toggles">
                <label class="toggle"><input type="hidden" name="is_enabled" value="0"><input type="checkbox" name="is_enabled" value="1" @checked(old('is_enabled', $game->is_enabled ?? true))><span><strong>Enabled</strong><small>Shown in the catalogue and allowed to sign players in.</small></span></label>
                <label class="toggle"><input type="hidden" name="is_featured" value="0"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $game->is_featured))><span><strong>Featured</strong><small>Can appear in the hero and sorts first.</small></span></label>
                <label class="toggle"><input type="hidden" name="is_dementia" value="0"><input type="checkbox" name="is_dementia" value="1" @checked(old('is_dementia', $game->is_dementia))><span><strong>Dementia-friendly</strong><small>Calm pace, simple rules, no time pressure. Shown as a catalogue filter.</small></span></label>
            </div>
        </fieldset>

        <fieldset>
            <legend>Sign-in (SSO)</legend>
            <label class="toggle"><input type="hidden" name="is_public" value="0"><input type="checkbox" name="is_public" value="1" @checked(old('is_public', $game->is_public))><span><strong>Public client</strong><small>Browser-only game: uses PKCE instead of a client secret.</small></span></label>
            <label>Allowed redirect URIs <textarea name="redirect_uris" rows="3" placeholder="https://chopper.nikniq.com/auth/callback">{{ old('redirect_uris', implode("\n", $game->redirect_uris ?? [])) }}</textarea><small>One per line. Must match exactly.</small></label>
        </fieldset>

        <fieldset>
            <legend>Notes</legend>
            <label>Admin notes <textarea name="notes" rows="3">{{ old('notes', $game->notes) }}</textarea><small>Only admins see these.</small></label>
        </fieldset>

        <div class="form-actions">
            <button class="btn" type="submit">{{ $game->exists ? 'Save changes' : 'Create game' }}</button>
            <a class="btn ghost" href="{{ route('admin.games.index') }}">Cancel</a>
            @if($game->exists)<a class="btn ghost" href="{{ route('games.show', $game) }}" target="_blank" rel="noopener">View public page ↗</a>@endif
        </div>
    </form>

    @if($game->exists)
        <form class="danger-zone" method="post" action="{{ route('admin.games.destroy', $game) }}" onsubmit="return confirm('Delete {{ $game->name }} and all of its favourites, tokens, events and stats?')">@csrf @method('DELETE')
            <div><strong>Delete this game</strong><p class="muted">Removes it with all favourites, tokens, events and stats. This cannot be undone. To hide it instead, untick Enabled.</p></div>
            <button class="btn danger" type="submit">Delete game</button>
        </form>
    @endif
    <script>
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-copy]'); if (!b || !navigator.clipboard) return;
        navigator.clipboard.writeText(b.dataset.copy).then(function () { var t = b.textContent; b.textContent = 'Copied'; setTimeout(function () { b.textContent = t; }, 1500); });
    });
    </script>
@endsection
