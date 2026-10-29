@extends('layouts.app')
@section('title', 'Terms of use')
@section('description', 'The rules for using '.config('portal.name').' and its games.')
@section('content')
<article class="prose">
    <h1>Terms of use</h1>
    <p class="muted">Last updated {{ \Illuminate\Support\Carbon::parse('2026-10-30')->format('j F Y') }}</p>
    <p>By using {{ config('portal.name') }} you agree to these terms. If you don't agree, please don't use the site.</p>

    <h2>The games</h2>
    <p>The games are free and provided as they are. We may add, change or remove games, scores or features at any time, and we can't promise the site or any game will always be available.</p>

    <h2>Your account</h2>
    <ul>
        <li>Keep your sign-in details to yourself; you are responsible for activity on your account.</li>
        <li>Choose a display name that is not offensive, misleading or someone else's. We may change or remove names that are.</li>
    </ul>

    <h2>Fair play</h2>
    <p>Don't cheat, tamper with scores, automate play, or interfere with the site or other players. We may remove scores, reset leaderboards, or suspend accounts that do.</p>

    <h2>Privacy</h2>
    <p>How we handle your information is described in the <a href="{{ route('privacy') }}">privacy policy</a>.</p>

    <h2>Liability</h2>
    <p>To the extent the law allows, we are not liable for any loss arising from your use of the site or the games.</p>

    <h2>Contact</h2>
    <p><a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a></p>
</article>
@endsection
