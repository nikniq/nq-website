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

    <h2>Elige cómo usa NikniQ tu juego</h2>
    <p>El inicio de sesión de NikniQ es opcional. Cada juego conserva el control de sus propias cuentas y sigue funcionando si el portal no está disponible; solo recibe un token y el perfil público del jugador, nunca acceso a la base de usuarios.</p>
    <table>
        <thead><tr><th>Enfoque</th><th>Cómo funciona</th><th>Los jugadores obtienen</th></tr></thead>
        <tbody>
            <tr><td><strong>Independiente</strong></td><td>Solo cuentas propias; el juego no carga <code>nikniq.js</code>.</td><td>Un inicio de sesión aparte. Las puntuaciones se quedan en el juego y el portal oculta su clasificación.</td></tr>
            <tr><td><strong>Vínculo opcional</strong></td><td>Inicio de sesión propio más un botón «Iniciar sesión con NikniQ». Usa <code>autoSignIn: false</code>.</td><td>Ellos eligen. Los jugadores vinculados obtienen puntuaciones, posición y favoritos en el portal.</td></tr>
            <tr><td><strong>Híbrido</strong></td><td>Inicio de sesión propio, pero primero se intenta NikniQ (por defecto).</td><td>Quien ya inició sesión en nikniq.com entra directamente; los demás ven tu inicio de sesión.</td></tr>
            <tr><td><strong>Solo NikniQ</strong></td><td>NikniQ es el único inicio de sesión del juego.</td><td>Una sola cuenta para todos los juegos de NikniQ.</td></tr>
        </tbody>
    </table>

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

    <h2>Mantener tu propio inicio de sesión (híbrido)</h2>
    <p>Espera a <code>nq.ready</code>. Si devuelve un jugador, ya había iniciado sesión en nikniq.com; si no, usa tu propio inicio de sesión. Si el portal no responde, <code>ready</code> falla y el juego sigue sin más.</p>
<pre><code>const nq = NikniQ.init({ clientId: '{{ $clientId }}', game: '{{ $slug }}' });
nq.ready
  .then(player =&gt; {
    if (player) useNikniqPlayer(player);   // conectado con NikniQ: asociarlo a tu propia cuenta
    else showOwnLogin();                    // sin sesión en nikniq.com
  })
  .catch(() =&gt; showOwnLogin());             // portal no disponible: seguir jugando</code></pre>

    <h2>OAuth 2.0 y OpenID Connect estándar</h2>
    <p>Los sitios con servidor propio, o cualquier framework con una biblioteca OAuth u OpenID Connect, pueden usar NikniQ como proveedor de identidad estándar en lugar del SDK. Indica a la biblioteca el documento de descubrimiento y configurará sola los endpoints (autorización, token, UserInfo, revocación, JWKS). Se admite el flujo de código de autorización con PKCE S256, los scopes openid, profile y email, tokens de identidad firmados con RS256 y <code>prompt=none</code>. Usa <code>sub</code>, no el correo, para vincular cuentas.</p>
<pre><code>{{ url('/.well-known/openid-configuration') }}</code></pre>

    <h2>Todo lo demás</h2>
    <table>
        <thead><tr><th>Llamada</th><th>Qué hace</th></tr></thead>
        <tbody>
            <tr><td><code>nq.player</code></td><td>El jugador con sesión iniciada (<code>id</code>, <code>name</code>, <code>avatar</code>) o <code>null</code></td></tr>
            <tr><td><code>nq.stats</code>, <code>nq.rank</code></td><td>Los totales del jugador y su puesto en la clasificación de este juego</td></tr>
            <tr><td><code>nq.leaderboard(10)</code></td><td>Las mejores puntuaciones, sin necesidad de iniciar sesión</td></tr>
            <tr><td><code>nq.setFavourite(true)</code></td><td>Añade o quita el juego de los favoritos del jugador</td></tr>
            <tr><td><code>autoSignIn</code></td><td>Ponlo en <code>false</code> para omitir la comprobación automática de sesión en la primera carga</td></tr>
            <tr><td><code>nq.signOut()</code></td><td>Revoca el token de este juego</td></tr>
        </tbody>
    </table>
    <p class="muted">El SDK usa el flujo estándar de código de autorización de OAuth con PKCE, así que el juego nunca incluye ningún secreto. Los juegos con servidor propio pueden usar la API directamente; consulta el README del proyecto.</p>
</article>
@endsection
