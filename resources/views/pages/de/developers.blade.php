@extends('layouts.app')
@section('title', 'Für Spieleentwickler')
@section('description', 'Bau NikniQ-Anmeldung, Punktestände und Bestenlisten mit einem einzigen Script-Tag in dein Browserspiel ein.')
@php($slug = request('game', $games->first()?->slug ?? 'your-game'))
@php($game = \App\Models\Game::where('slug', $slug)->first())
@php($clientId = auth()->user()?->is_admin && $game ? $game->client_id : 'CLIENT_ID')
@section('content')
<article class="prose">
    <h1>NikniQ in dein Spiel einbauen</h1>
    <p class="lead">Ein einziger Script-Tag gibt deinem Spiel die NikniQ-Anmeldung, gespeicherte Punktestände, den Rang des Spielers und die gemeinsame Bestenliste. Die Ergebnisse der Spieler erscheinen dann in ihrem Dashboard, auf ihrer Spielerseite und in der Ruhmeshalle.</p>

    <h2>1. Spiel registrieren</h2>
    <p>Ein Admin legt das Spiel unter <a href="{{ route('admin.games.index') }}">Admin → Spiele</a> an, setzt bei einem reinen Browserspiel das Häkchen bei <em>Public client</em> und trägt die genaue Seite, auf die der Spieler zurückkehrt, als Redirect-URI ein, zum Beispiel <code>https://{{ $slug }}.nikniq.com/</code>. Die Client-ID des Spiels steht auf seiner Admin-Seite.</p>

    <h2>2. Script einbinden</h2>
    @if(auth()->user()?->is_admin)
        <form method="get" class="row" style="margin-bottom:.6rem">
            <label class="inline">Snippet für
                <select name="game" onchange="this.form.submit()">
                    @foreach($games as $g)<option value="{{ $g->slug }}" @selected($g->slug === $slug)>{{ $g->name }}</option>@endforeach
                </select>
            </label>
            <noscript><button class="btn sm" type="submit">Anzeigen</button></noscript>
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

  signInButton.onclick = () =&gt; nq.signIn();   // geht zu NikniQ und zurück
&lt;/script&gt;</code></pre>

    <h2>3. Melden, was passiert</h2>
<pre><code>nq.report({ event: 'level_complete', level: '3', score: 820 });
nq.report({ event: 'game_over', score: 1240, won: false, duration_seconds: 87 })
  .then(res =&gt; res &amp;&amp; showRank(res.rank));   // aktualisierte Statistiken und Rang</code></pre>
    <ul>
        <li><code>game_over</code>, <code>finish</code>, <code>complete</code>, <code>round_end</code> und <code>session_end</code> zählen als eine Partie und werden sofort gesendet. Andere Ereignisse werden gesammelt und innerhalb weniger Sekunden gesendet oder wenn die Seite geschlossen wird.</li>
        <li>Felder: <code>event</code> (Pflicht; Buchstaben, Ziffern, <code>_ . -</code>), <code>score</code>, <code>level</code>, <code>duration_seconds</code>, <code>won</code>, <code>metadata</code> (ein Objekt mit bis zu 50 Schlüsseln).</li>
        <li>Ist niemand angemeldet, wird nichts gesendet; das Spiel funktioniert für Gäste also genauso.</li>
    </ul>

    <h2>Alles andere</h2>
    <table>
        <thead><tr><th>Aufruf</th><th>Was er macht</th></tr></thead>
        <tbody>
            <tr><td><code>nq.player</code></td><td>Der angemeldete Spieler (<code>id</code>, <code>name</code>, <code>avatar</code>) oder <code>null</code></td></tr>
            <tr><td><code>nq.stats</code>, <code>nq.rank</code></td><td>Die Gesamtwerte des Spielers und sein Rang in der Bestenliste dieses Spiels</td></tr>
            <tr><td><code>nq.leaderboard(10)</code></td><td>Bestwerte, ohne Anmeldung</td></tr>
            <tr><td><code>nq.setFavourite(true)</code></td><td>Fügt das Spiel den Favoriten des Spielers hinzu oder entfernt es</td></tr>
            <tr><td><code>nq.signOut()</code></td><td>Widerruft das Token dieses Spiels</td></tr>
        </tbody>
    </table>
    <p class="muted">Das SDK nutzt den standardmäßigen OAuth-Authorization-Code-Flow mit PKCE, daher steckt nie ein Secret im Spiel. Spiele mit eigenem Server können stattdessen direkt die API nutzen; siehe die README des Projekts.</p>
</article>
@endsection
