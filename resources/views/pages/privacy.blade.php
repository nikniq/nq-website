@extends('layouts.app')
@section('title', 'Privacy policy')
@section('description', 'What '.config('portal.name').' collects, why, and how to have it removed.')
@section('content')
<article class="prose">
    <h1>Privacy policy</h1>
    <p class="muted">Last updated {{ \Illuminate\Support\Carbon::parse('2026-10-30')->format('j F Y') }}</p>
    <p>{{ config('portal.name') }} ({{ parse_url(config('app.url'), PHP_URL_HOST) }}) is a portal for free browser games. This page explains what we collect and why. You can play every game without an account; an account only adds favourites, saved scores and leaderboards.</p>

    <h2>What we collect</h2>
    <ul>
        <li><strong>Account details:</strong> your email address, display name and, if you set one, a password (stored only as a one-way hash).</li>
        <li><strong>Google sign-in:</strong> if you sign in with Google we receive your name, email address, Google account id and profile photo. We do not receive your Google password or access to anything else.</li>
        <li><strong>Game activity:</strong> when you play a game signed in with your account, the game sends us events such as scores, levels, wins and time played. We keep these to build your dashboard, rankings and leaderboards.</li>
        <li><strong>Favourites</strong> you choose and the games you launch from the portal.</li>
        <li><strong>Technical data:</strong> your IP address and browser details in server logs, kept for security and troubleshooting.</li>
    </ul>

    <h2>What is public</h2>
    <p>Your display name, profile photo, best scores and favourite games appear on leaderboards, the hall of fame and your player page. You can hide your player page (which also removes you from the hall of fame) from your <a href="{{ route('profile') }}">profile</a>. Your email address is never shown publicly.</p>

    <h2>Cookies and local storage</h2>
    <ul>
        <li>A session cookie keeps you signed in, and a "remember me" cookie if you ask for it.</li>
        <li>Your light/dark and calm-mode choices are stored in your browser only.</li>
        <li>We use Google Analytics to understand which pages are used. It sets its own cookies and Google processes that data under its own privacy policy.</li>
    </ul>

    <h2>Who we share it with</h2>
    <p>We do not sell your data. Games you sign in to through the portal receive your name, email, photo and your stats for that game. Our hosting provider, email provider and Google (sign-in and analytics) process data on our behalf.</p>

    <h2>Your choices</h2>
    <ul>
        <li>Change your name, email or password, hide your player page, and sign out of individual games from your <a href="{{ route('profile') }}">profile</a>.</li>
        <li>To delete your account and its scores, email us at <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>.</li>
    </ul>

    <h2>Contact</h2>
    <p>Questions about this policy: <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>.</p>
</article>
@endsection
