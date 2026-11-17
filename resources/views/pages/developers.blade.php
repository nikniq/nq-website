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

    <h2>Choose how your game uses NikniQ</h2>
    <p>NikniQ sign-in is optional. Every game keeps control of its own accounts and keeps working if the portal is unreachable; it only receives a token and the player's public profile, never access to the user database.</p>
    <table>
        <thead><tr><th>Approach</th><th>How it works</th><th>Players get</th></tr></thead>
        <tbody>
            <tr><td><strong>Independent</strong></td><td>Own accounts only; the game never loads <code>nikniq.js</code>.</td><td>A separate login. Scores stay in the game, and the portal hides its leaderboard.</td></tr>
            <tr><td><strong>Optional link</strong></td><td>Own login, plus a “Sign in with NikniQ” button. Set <code>autoSignIn: false</code>.</td><td>Their choice. Linked players get portal scores, rank and favourites.</td></tr>
            <tr><td><strong>Hybrid</strong></td><td>Own login, but try NikniQ first (the default).</td><td>Players logged in on nikniq.com go straight in; everyone else sees your login.</td></tr>
            <tr><td><strong>NikniQ only</strong></td><td>NikniQ is the game's only login.</td><td>One account across every NikniQ game.</td></tr>
        </tbody>
    </table>

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
    <p>Players already logged in on nikniq.com are signed in automatically: on its first load in a tab, the SDK makes a quick round trip to the portal and comes back signed in, or as a guest if they aren't logged in. Pass <code>autoSignIn: false</code> to <code>NikniQ.init</code> to turn this off.</p>

    <h2>3. Report what happens</h2>
<pre><code>nq.report({ event: 'level_complete', level: '3', score: 820 });
nq.report({ event: 'game_over', score: 1240, won: false, duration_seconds: 87 })
  .then(res =&gt; res &amp;&amp; showRank(res.rank));   // updated stats and rank</code></pre>
    <ul>
        <li><code>game_over</code>, <code>finish</code>, <code>complete</code>, <code>round_end</code> and <code>session_end</code> count as one play and are sent straight away. Other events are batched and sent within a few seconds, or when the page closes.</li>
        <li>Fields: <code>event</code> (required, letters, digits, <code>_ . -</code>), <code>score</code>, <code>level</code>, <code>duration_seconds</code>, <code>won</code>, <code>metadata</code> (an object of up to 50 keys).</li>
        <li>Nothing is sent when nobody is signed in, so the game works the same for guests.</li>
    </ul>

    <h2>Keeping your own login (hybrid)</h2>
    <p>Wait for <code>nq.ready</code>. If it gives you a player, they were already logged in on nikniq.com; otherwise fall back to your own login. If the portal can't be reached, <code>ready</code> rejects and the game simply carries on.</p>
<pre><code>const nq = NikniQ.init({ clientId: '{{ $clientId }}', game: '{{ $slug }}' });
nq.ready
  .then(player =&gt; {
    if (player) useNikniqPlayer(player);   // signed in through NikniQ: map to your own account
    else showOwnLogin();                    // not logged in on nikniq.com
  })
  .catch(() =&gt; showOwnLogin());             // portal unreachable: play on</code></pre>

    <h2>Standard OAuth 2.0 and OpenID Connect</h2>
    <p>Sites with their own server, or any framework with an OAuth or OpenID Connect library (Laravel Socialite, Auth.js/NextAuth, Passport.js, Spring Security, oidc-client-ts and others), can use NikniQ as a standard identity provider instead of the SDK. Point the library at the discovery document and it configures itself:</p>
<pre><code>{{ url('/.well-known/openid-configuration') }}</code></pre>
    <table>
        <thead><tr><th>Setting</th><th>Value</th></tr></thead>
        <tbody>
            <tr><td>Flow</td><td>Authorization code (<code>response_type=code</code>), with PKCE <code>S256</code>; PKCE is required for public clients</td></tr>
            <tr><td>Authorization endpoint</td><td><code>{{ route('sso.authorize') }}</code></td></tr>
            <tr><td>Token endpoint</td><td><code>{{ route('api.sso.token') }}</code> (client auth: <code>client_secret_basic</code>, <code>client_secret_post</code>, or none with PKCE)</td></tr>
            <tr><td>UserInfo</td><td><code>{{ route('api.oauth.userinfo') }}</code></td></tr>
            <tr><td>Revocation</td><td><code>{{ route('api.oauth.revoke') }}</code> (RFC 7009)</td></tr>
            <tr><td>Keys</td><td><code>{{ route('api.oauth.jwks') }}</code>; ID tokens are signed RS256</td></tr>
            <tr><td>Scopes</td><td><code>openid</code> (ID token), <code>profile</code> (name, picture), <code>email</code> (email, email_verified)</td></tr>
            <tr><td>Silent sign-in</td><td><code>prompt=none</code> returns a code if the player is logged in on nikniq.com, otherwise <code>error=login_required</code></td></tr>
        </tbody>
    </table>
    <p>The client ID, the client secret (for server-side apps) and the allowed redirect URIs are set when the site is registered in Admin → Games. The <code>sub</code> claim is the player's stable NikniQ ID; use it, not the email, to link accounts. Access tokens last {{ config('portal.token_ttl_days') }} days and also work with the score and favourite APIs above. There are no refresh tokens: when a token expires, send the player through the authorization endpoint again (with <code>prompt=none</code> it is invisible).</p>

    <h2>Everything else</h2>
    <table>
        <thead><tr><th>Call</th><th>What it does</th></tr></thead>
        <tbody>
            <tr><td><code>nq.player</code></td><td>The signed-in player (<code>id</code>, <code>name</code>, <code>avatar</code>) or <code>null</code></td></tr>
            <tr><td><code>nq.stats</code>, <code>nq.rank</code></td><td>The player's totals and leaderboard rank in this game</td></tr>
            <tr><td><code>nq.leaderboard(10)</code></td><td>Top scores, no sign-in needed</td></tr>
            <tr><td><code>nq.setFavourite(true)</code></td><td>Add or remove the game from the player's favourites</td></tr>
            <tr><td><code>autoSignIn</code></td><td>Set to <code>false</code> to skip the automatic sign-in check on first load</td></tr>
            <tr><td><code>nq.signOut()</code></td><td>Revoke this game's token</td></tr>
        </tbody>
    </table>
    <p class="muted">The SDK uses the standard OAuth authorization-code flow with PKCE, so no secret ever ships in the game. Games with their own server can use the raw API instead; see the project README.</p>
</article>
@endsection
