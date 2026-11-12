@extends('layouts.app')
@section('title', 'Politique de confidentialité')
@section('description', 'Ce que '.config('portal.name').' collecte, pourquoi, et comment le faire supprimer.')
@section('content')
<article class="prose">
    <h1>Politique de confidentialité</h1>
    <p class="muted">Cette traduction est fournie pour votre confort. Seule la <a href="/privacy?lang=en">version anglaise</a> fait foi.</p>
    <p class="muted">Dernière mise à jour le {{ \Illuminate\Support\Carbon::parse('2026-10-30')->locale('fr')->translatedFormat('j F Y') }}</p>
    <p>{{ config('portal.name') }} ({{ parse_url(config('app.url'), PHP_URL_HOST) }}) est un portail de jeux gratuits dans le navigateur. Cette page explique ce que nous collectons et pourquoi. Vous pouvez jouer à tous les jeux sans compte ; un compte ajoute simplement les favoris, les scores enregistrés et les classements.</p>

    <h2>Ce que nous collectons</h2>
    <ul>
        <li><strong>Informations du compte :</strong> votre adresse e-mail, votre nom d’affichage et, si vous en choisissez un, un mot de passe (conservé uniquement sous forme de hachage irréversible).</li>
        <li><strong>Connexion avec Google :</strong> si vous vous connectez avec Google, nous recevons votre nom, votre adresse e-mail, l’identifiant de votre compte Google et votre photo de profil. Nous ne recevons ni votre mot de passe Google ni l’accès à quoi que ce soit d’autre.</li>
        <li><strong>Activité de jeu :</strong> lorsque vous jouez à un jeu connecté à votre compte, le jeu nous envoie des événements comme les scores, les niveaux, les victoires et le temps de jeu. Nous les conservons pour construire votre tableau de bord, vos rangs et les classements.</li>
        <li>Les <strong>favoris</strong> que vous choisissez et les jeux que vous lancez depuis le portail.</li>
        <li><strong>Données techniques :</strong> votre adresse IP et les informations de votre navigateur dans les journaux du serveur, conservées pour la sécurité et le dépannage.</li>
    </ul>

    <h2>Ce qui est public</h2>
    <p>Votre nom d’affichage, votre photo de profil, vos meilleurs scores et vos jeux favoris apparaissent dans les classements, au Panthéon et sur votre page joueur. Vous pouvez masquer votre page joueur (ce qui vous retire aussi du Panthéon) depuis votre <a href="{{ route('profile') }}">profil</a>. Votre adresse e-mail n’est jamais affichée publiquement.</p>

    <h2>Cookies et stockage local</h2>
    <ul>
        <li>Un cookie de session vous garde connecté, ainsi qu’un cookie « se souvenir de moi » si vous le demandez.</li>
        <li>Vos choix de thème clair ou sombre et de mode calme sont enregistrés uniquement dans votre navigateur.</li>
        <li>Nous utilisons Google Analytics pour comprendre quelles pages sont consultées. Il dépose ses propres cookies et Google traite ces données selon sa propre politique de confidentialité.</li>
    </ul>

    <h2>Avec qui nous les partageons</h2>
    <p>Nous ne vendons pas vos données. Les jeux auxquels vous vous connectez via le portail reçoivent votre nom, votre e-mail, votre photo et vos statistiques pour ce jeu. Notre hébergeur, notre fournisseur d’e-mail et Google (connexion et statistiques d’audience) traitent des données pour notre compte.</p>

    <h2>Vos choix</h2>
    <ul>
        <li>Modifiez votre nom, votre e-mail ou votre mot de passe, masquez votre page joueur et déconnectez-vous de certains jeux depuis votre <a href="{{ route('profile') }}">profil</a>.</li>
        <li>Téléchargez une copie de toutes vos données, ou supprimez définitivement votre compte et tout ce qui y est rattaché, depuis votre <a href="{{ route('profile') }}">profil</a>. Vous pouvez aussi nous écrire à <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>.</li>
    </ul>

    <h2>Contact</h2>
    <p>Pour toute question sur cette politique : <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>.</p>
</article>
@endsection
