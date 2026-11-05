@extends('layouts.app')
@section('title', 'For game developers')
@section('description', 'Add NikniQ sign-in, scores and leaderboards to a browser game with one script tag.')
@php($slug = request('game', $games->first()?->slug ?? 'your-game'))
@php($game = \App\Models\Game::where('slug', $slug)->first())
@php($clientId = auth()->user()?->is_admin && $game ? $game->client_id : 'CLIENT_ID')
@section('content')
<article class="prose">
    <h1>Add NikniQ to your game</h1>
    <p class="lead">One script tag gives your game NikniQ sign-in, saved scores, the player's rank and the shared leaderboard. Players' results then appear on their dashboard, player page and the hall of fame.</p>

    <h2>1. Register the game</h2>
    <p>An admin adds the game at <a href="{{ route('admin.games.index') }}">Admin → Games</a>, ticks <em>Public client</em> for a browser-only game, and lists the exact page the player returns to, for example <code>https://{{ $slug }}.nikniq.com/</code>, as a redirect URI. The game's client ID is shown on its admin page.</p>

    <h2>2. Add the script</h2>
    @if(auth()->user()?->is_admin)
        <form method="get" class="row" style="margin-bottom:.6rem">
            <label class="inline">Snippet for
                <select name="game" onchange="this.form.submit()">
                    @foreach($games as $g)<option value="{{ $g->slug }}" @selected($g->slug === $slug)>{{ $g->name }}</option>@endforeach
                </select>
            </label>
            <noscript><button class="btn sm" type="submit">Show</button></noscript>
        </form>
    @endif
<pre><code>&lt;script src="{{ url('/js/nikniq.js') }}"&gt;&lt;/script&gt;
&lt;script&gt;
  const nq = NikniQ.init({ clientId: '{{ $clientId }}', game: '{{ $slug }}' });

  nq.onChange(player =&gt; {
    signInButton.hidden = !!player;
    playerName.textContent = player ? player.name : '';
  });
  nq.ready.catch(err =&gt; console.warn('NikniQ sign-in failed:', err.message));

  signInButton.onclick = () =&gt; nq.signIn();   // goes to NikniQ and back
&lt;/script&gt;</code></pre>

    <h2>3. Report what happens</h2>
<pre><code>nq.report({ event: 'level_complete', level: '3', score: 820 });
nq.report({ event: 'game_over', score: 1240, won: false, duration_seconds: 87 })
  .then(res =&gt; res &amp;&amp; showRank(res.rank));   // updated stats and rank</code></pre>
    <ul>
        <li><code>game_over</code>, <code>finish</code>, <code>complete</code>, <code>round_end</code> and <code>session_end</code> count as one play and are sent straight away. Other events are batched and sent within a few seconds, or when the page closes.</li>
        <li>Fields: <code>event</code> (required, letters, digits, <code>_ . -</code>), <code>score</code>, <code>level</code>, <code>duration_seconds</code>, <code>won</code>, <code>metadata</code> (an object of up to 50 keys).</li>
        <li>Nothing is sent when nobody is signed in, so the game works the same for guests.</li>
    </ul>

    <h2>Everything else</h2>
    <table>
        <thead><tr><th>Call</th><th>What it does</th></tr></thead>
        <tbody>
            <tr><td><code>nq.player</code></td><td>The signed-in player (<code>id</code>, <code>name</code>, <code>avatar</code>) or <code>null</code></td></tr>
            <tr><td><code>nq.stats</code>, <code>nq.rank</code></td><td>The player's totals and leaderboard rank in this game</td></tr>
            <tr><td><code>nq.leaderboard(10)</code></td><td>Top scores, no sign-in needed</td></tr>
            <tr><td><code>nq.setFavourite(true)</code></td><td>Add or remove the game from the player's favourites</td></tr>
            <tr><td><code>nq.signOut()</code></td><td>Revoke this game's token</td></tr>
        </tbody>
    </table>
    <p class="muted">The SDK uses the standard OAuth authorization-code flow with PKCE, so no secret ever ships in the game. Games with their own server can use the raw API instead; see the project README.</p>
</article>
@endsection
