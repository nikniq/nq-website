@extends('layouts.app')
@section('title', 'Pour les développeurs de jeux')
@section('description', 'Ajoutez la connexion NikniQ, les scores et les classements à un jeu dans le navigateur avec une seule balise script.')
@php($slug = request('game', $games->first()?->slug ?? 'your-game'))
@php($game = \App\Models\Game::where('slug', $slug)->first())
@php($clientId = auth()->user()?->is_admin && $game ? $game->client_id : 'CLIENT_ID')
@section('content')
<article class="prose">
    <h1>Ajoutez NikniQ à votre jeu</h1>
    <p class="lead">Une seule balise script donne à votre jeu la connexion NikniQ, les scores enregistrés, le rang du joueur et le classement partagé. Les résultats des joueurs apparaissent ensuite sur leur tableau de bord, leur page joueur et au Panthéon.</p>

    <h2>Choisir comment votre jeu utilise NikniQ</h2>
    <p>La connexion NikniQ est facultative. Chaque jeu garde la maîtrise de ses propres comptes et continue de fonctionner si le portail est injoignable ; il ne reçoit qu’un jeton et le profil public du joueur, jamais l’accès à la base des utilisateurs.</p>
    <table>
        <thead><tr><th>Approche</th><th>Fonctionnement</th><th>Pour les joueurs</th></tr></thead>
        <tbody>
            <tr><td><strong>Indépendant</strong></td><td>Comptes propres uniquement ; le jeu ne charge pas <code>nikniq.js</code>.</td><td>Une connexion séparée. Les scores restent dans le jeu et le portail masque son classement.</td></tr>
            <tr><td><strong>Lien facultatif</strong></td><td>Connexion propre, plus un bouton « Se connecter avec NikniQ ». Mettez <code>autoSignIn: false</code>.</td><td>Au choix. Les joueurs liés obtiennent scores, rang et favoris sur le portail.</td></tr>
            <tr><td><strong>Hybride</strong></td><td>Connexion propre, mais NikniQ est essayé d’abord (par défaut).</td><td>Les joueurs connectés sur nikniq.com entrent directement ; les autres voient votre connexion.</td></tr>
            <tr><td><strong>NikniQ seul</strong></td><td>NikniQ est la seule connexion du jeu.</td><td>Un seul compte pour tous les jeux NikniQ.</td></tr>
        </tbody>
    </table>

    <h2>1. Enregistrer le jeu</h2>
    <p>Un administrateur ajoute le jeu dans <a href="{{ route('admin.games.index') }}">Administration → Jeux</a>, coche <em>Public client</em> pour un jeu qui tourne uniquement dans le navigateur, et indique comme URI de redirection la page exacte où revient le joueur, par exemple <code>https://{{ $slug }}.nikniq.com/</code>. L’identifiant client (client ID) du jeu figure sur sa page d’administration.</p>

    <h2>2. Ajouter le script</h2>
    @if(auth()->user()?->is_admin)
        <form method="get" class="row" style="margin-bottom:.6rem">
            <label class="inline">Extrait pour
                <select name="game" onchange="this.form.submit()">
                    @foreach($games as $g)<option value="{{ $g->slug }}" @selected($g->slug === $slug)>{{ $g->name }}</option>@endforeach
                </select>
            </label>
            <noscript><button class="btn sm" type="submit">Afficher</button></noscript>
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

  signInButton.onclick = () =&gt; nq.signIn();   // va sur NikniQ puis revient
&lt;/script&gt;</code></pre>

    <h2>Connexion avec NikniQ ou Google</h2>
    <p>Proposez les deux boutons. La connexion Google passe par NikniQ : le joueur obtient quand même un compte NikniQ (créé à la première utilisation ou lié par e-mail vérifié), et scores, classements et favoris fonctionnent de la même façon.</p>
<pre><code>nq.mountSignIn(document.getElementById('signin'));
googleButton.onclick = () =&gt; nq.signIn({ provider: 'google' });</code></pre>

    <h2>3. Signaler ce qui se passe</h2>
<pre><code>nq.report({ event: 'level_complete', level: '3', score: 820 });
nq.report({ event: 'game_over', score: 1240, won: false, duration_seconds: 87 })
  .then(res =&gt; res &amp;&amp; showRank(res.rank));   // statistiques et rang mis à jour</code></pre>
    <ul>
        <li><code>game_over</code>, <code>finish</code>, <code>complete</code>, <code>round_end</code> et <code>session_end</code> comptent comme une partie et sont envoyés immédiatement. Les autres événements sont regroupés et envoyés en quelques secondes, ou à la fermeture de la page.</li>
        <li>Champs : <code>event</code> (obligatoire ; lettres, chiffres, <code>_ . -</code>), <code>score</code>, <code>level</code>, <code>duration_seconds</code>, <code>won</code>, <code>metadata</code> (un objet de 50 clés au maximum).</li>
        <li>Rien n’est envoyé quand personne n’est connecté : le jeu fonctionne de la même façon pour les invités.</li>
    </ul>

    <h2>Garder votre propre connexion (hybride)</h2>
    <p>Attendez <code>nq.ready</code>. S’il renvoie un joueur, celui-ci était déjà connecté sur nikniq.com ; sinon, utilisez votre propre connexion. Si le portail est injoignable, <code>ready</code> échoue et le jeu continue simplement.</p>
<pre><code>const nq = NikniQ.init({ clientId: '{{ $clientId }}', game: '{{ $slug }}' });
nq.ready
  .then(player =&gt; {
    if (player) useNikniqPlayer(player);   // connecté via NikniQ : associer à votre propre compte
    else showOwnLogin();                    // pas connecté sur nikniq.com
  })
  .catch(() =&gt; showOwnLogin());             // portail injoignable : on continue</code></pre>

    <h2>OAuth 2.0 et OpenID Connect standard</h2>
    <p>Les sites dotés de leur propre serveur, ou tout framework disposant d’une bibliothèque OAuth ou OpenID Connect, peuvent utiliser NikniQ comme fournisseur d’identité standard au lieu du SDK. Indiquez le document de découverte à la bibliothèque : elle configure seule les points d’accès (autorisation, jeton, UserInfo, révocation, JWKS). Flux code d’autorisation avec PKCE S256, scopes openid, profile et email, jetons d’identité signés RS256 et <code>prompt=none</code>. Utilisez <code>sub</code>, et non l’e-mail, pour lier les comptes.</p>
<pre><code>{{ url('/.well-known/openid-configuration') }}</code></pre>

    <h2>Tout le reste</h2>
    <table>
        <thead><tr><th>Appel</th><th>Ce qu’il fait</th></tr></thead>
        <tbody>
            <tr><td><code>nq.player</code></td><td>Le joueur connecté (<code>id</code>, <code>name</code>, <code>avatar</code>) ou <code>null</code></td></tr>
            <tr><td><code>nq.stats</code>, <code>nq.rank</code></td><td>Les totaux du joueur et son rang au classement de ce jeu</td></tr>
            <tr><td><code>nq.leaderboard(10)</code></td><td>Meilleurs scores, sans connexion</td></tr>
            <tr><td><code>nq.setFavourite(true)</code></td><td>Ajoute le jeu aux favoris du joueur ou l’en retire</td></tr>
            <tr><td><code>autoSignIn</code></td><td>Mettre à <code>false</code> pour ne pas vérifier automatiquement la connexion au premier chargement</td></tr>
            <tr><td><code>nq.signIn({ provider: 'google' })</code></td><td>Se connecter avec Google (via NikniQ)</td></tr>
            <tr><td><code>nq.mountSignIn(el)</code></td><td>Affiche les boutons de connexion NikniQ et Google</td></tr>
            <tr><td><code>nq.signOut()</code></td><td>Révoque le jeton de ce jeu</td></tr>
        </tbody>
    </table>
    <p class="muted">Le SDK utilise le flux OAuth standard par code d’autorisation avec PKCE : aucun secret n’est jamais livré avec le jeu. Les jeux qui ont leur propre serveur peuvent utiliser directement l’API ; voir le README du projet.</p>
</article>
@endsection
