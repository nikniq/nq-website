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

    <h2>3. Signaler ce qui se passe</h2>
<pre><code>nq.report({ event: 'level_complete', level: '3', score: 820 });
nq.report({ event: 'game_over', score: 1240, won: false, duration_seconds: 87 })
  .then(res =&gt; res &amp;&amp; showRank(res.rank));   // statistiques et rang mis à jour</code></pre>
    <ul>
        <li><code>game_over</code>, <code>finish</code>, <code>complete</code>, <code>round_end</code> et <code>session_end</code> comptent comme une partie et sont envoyés immédiatement. Les autres événements sont regroupés et envoyés en quelques secondes, ou à la fermeture de la page.</li>
        <li>Champs : <code>event</code> (obligatoire ; lettres, chiffres, <code>_ . -</code>), <code>score</code>, <code>level</code>, <code>duration_seconds</code>, <code>won</code>, <code>metadata</code> (un objet de 50 clés au maximum).</li>
        <li>Rien n’est envoyé quand personne n’est connecté : le jeu fonctionne de la même façon pour les invités.</li>
    </ul>

    <h2>Tout le reste</h2>
    <table>
        <thead><tr><th>Appel</th><th>Ce qu’il fait</th></tr></thead>
        <tbody>
            <tr><td><code>nq.player</code></td><td>Le joueur connecté (<code>id</code>, <code>name</code>, <code>avatar</code>) ou <code>null</code></td></tr>
            <tr><td><code>nq.stats</code>, <code>nq.rank</code></td><td>Les totaux du joueur et son rang au classement de ce jeu</td></tr>
            <tr><td><code>nq.leaderboard(10)</code></td><td>Meilleurs scores, sans connexion</td></tr>
            <tr><td><code>nq.setFavourite(true)</code></td><td>Ajoute le jeu aux favoris du joueur ou l’en retire</td></tr>
            <tr><td><code>nq.signOut()</code></td><td>Révoque le jeton de ce jeu</td></tr>
        </tbody>
    </table>
    <p class="muted">Le SDK utilise le flux OAuth standard par code d’autorisation avec PKCE : aucun secret n’est jamais livré avec le jeu. Les jeux qui ont leur propre serveur peuvent utiliser directement l’API ; voir le README du projet.</p>
</article>
@endsection
