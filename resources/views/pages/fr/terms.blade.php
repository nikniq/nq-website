@extends('layouts.app')
@section('title', 'Conditions d’utilisation')
@section('description', 'Les règles d’utilisation de '.config('portal.name').' et de ses jeux.')
@section('content')
<article class="prose">
    <h1>Conditions d’utilisation</h1>
    <p class="muted">Cette traduction est fournie pour votre confort. Seule la <a href="/terms?lang=en">version anglaise</a> fait foi.</p>
    <p class="muted">Dernière mise à jour le {{ \Illuminate\Support\Carbon::parse('2026-10-30')->locale('fr')->translatedFormat('j F Y') }}</p>
    <p>En utilisant {{ config('portal.name') }}, vous acceptez ces conditions. Si vous ne les acceptez pas, merci de ne pas utiliser le site.</p>

    <h2>Les jeux</h2>
    <p>Les jeux sont gratuits et fournis tels quels. Nous pouvons ajouter, modifier ou retirer des jeux, des scores ou des fonctionnalités à tout moment, et nous ne pouvons pas garantir que le site ou un jeu sera toujours disponible.</p>

    <h2>Votre compte</h2>
    <ul>
        <li>Gardez vos identifiants de connexion pour vous ; vous êtes responsable de l’activité de votre compte.</li>
        <li>Choisissez un nom d’affichage qui ne soit ni offensant, ni trompeur, ni celui de quelqu’un d’autre. Nous pouvons modifier ou supprimer les noms qui le sont.</li>
    </ul>

    <h2>Jeu loyal</h2>
    <p>Ne trichez pas, ne falsifiez pas les scores, n’automatisez pas le jeu et ne perturbez ni le site ni les autres joueurs. Nous pouvons supprimer des scores, réinitialiser des classements ou suspendre les comptes concernés.</p>

    <h2>Confidentialité</h2>
    <p>La façon dont nous traitons vos informations est décrite dans la <a href="{{ route('privacy') }}">politique de confidentialité</a>.</p>

    <h2>Responsabilité</h2>
    <p>Dans la mesure permise par la loi, nous ne sommes pas responsables des pertes résultant de votre utilisation du site ou des jeux.</p>

    <h2>Contact</h2>
    <p><a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a></p>
</article>
@endsection
