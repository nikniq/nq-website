@extends('layouts.app')
@section('title', 'Para desarrolladores de juegos')
@section('description', 'Añade el inicio de sesión, las puntuaciones y las clasificaciones de NikniQ a un juego de navegador con una sola etiqueta script.')
@php($slug = request('game', $games->first()?->slug ?? 'your-game'))
@php($game = \App\Models\Game::where('slug', $slug)->first())
@php($clientId = auth()->user()?->is_admin && $game ? $game->client_id : 'CLIENT_ID')
@section('content')
<article class="prose">
    <h1>Añade NikniQ a tu juego</h1>
    <p class="lead">Una sola etiqueta script le da a tu juego el inicio de sesión de NikniQ, puntuaciones guardadas, el puesto del jugador y la clasificación compartida. Los resultados de los jugadores aparecen luego en su panel, en su página de jugador y en el salón de la fama.</p>

    <h2>1. Registra el juego</h2>
    <p>Un administrador añade el juego en <a href="{{ route('admin.games.index') }}">Administración → Juegos</a>, marca <em>Public client</em> si el juego funciona solo en el navegador e indica como URI de redirección la página exacta a la que vuelve el jugador, por ejemplo <code>https://{{ $slug }}.nikniq.com/</code>. El ID de cliente del juego aparece en su página de administración.</p>

    <h2>2. Añade el script</h2>
    @if(auth()->user()?->is_admin)
        <form method="get" class="row" style="margin-bottom:.6rem">
            <label class="inline">Código para
                <select name="game" onchange="this.form.submit()">
                    @foreach($games as $g)<option value="{{ $g->slug }}" @selected($g->slug === $slug)>{{ $g->name }}</option>@endforeach
                </select>
            </label>
            <noscript><button class="btn sm" type="submit">Mostrar</button></noscript>
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

  signInButton.onclick = () =&gt; nq.signIn();   // va a NikniQ y vuelve
&lt;/script&gt;</code></pre>

    <h2>3. Informa de lo que pasa</h2>
<pre><code>nq.report({ event: 'level_complete', level: '3', score: 820 });
nq.report({ event: 'game_over', score: 1240, won: false, duration_seconds: 87 })
  .then(res =&gt; res &amp;&amp; showRank(res.rank));   // estadísticas y puesto actualizados</code></pre>
    <ul>
        <li><code>game_over</code>, <code>finish</code>, <code>complete</code>, <code>round_end</code> y <code>session_end</code> cuentan como una partida y se envían al momento. Los demás eventos se agrupan y se envían en pocos segundos, o al cerrar la página.</li>
        <li>Campos: <code>event</code> (obligatorio; letras, dígitos, <code>_ . -</code>), <code>score</code>, <code>level</code>, <code>duration_seconds</code>, <code>won</code>, <code>metadata</code> (un objeto de hasta 50 claves).</li>
        <li>No se envía nada si nadie ha iniciado sesión, así que el juego funciona igual para los invitados.</li>
    </ul>

    <h2>Todo lo demás</h2>
    <table>
        <thead><tr><th>Llamada</th><th>Qué hace</th></tr></thead>
        <tbody>
            <tr><td><code>nq.player</code></td><td>El jugador con sesión iniciada (<code>id</code>, <code>name</code>, <code>avatar</code>) o <code>null</code></td></tr>
            <tr><td><code>nq.stats</code>, <code>nq.rank</code></td><td>Los totales del jugador y su puesto en la clasificación de este juego</td></tr>
            <tr><td><code>nq.leaderboard(10)</code></td><td>Las mejores puntuaciones, sin necesidad de iniciar sesión</td></tr>
            <tr><td><code>nq.setFavourite(true)</code></td><td>Añade o quita el juego de los favoritos del jugador</td></tr>
            <tr><td><code>nq.signOut()</code></td><td>Revoca el token de este juego</td></tr>
        </tbody>
    </table>
    <p class="muted">El SDK usa el flujo estándar de código de autorización de OAuth con PKCE, así que el juego nunca incluye ningún secreto. Los juegos con servidor propio pueden usar la API directamente; consulta el README del proyecto.</p>
</article>
@endsection
