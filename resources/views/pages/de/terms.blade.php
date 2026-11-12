@extends('layouts.app')
@section('title', 'Nutzungsbedingungen')
@section('description', 'Die Regeln für die Nutzung von '.config('portal.name').' und seinen Spielen.')
@section('content')
<article class="prose">
    <h1>Nutzungsbedingungen</h1>
    <p class="muted">Diese Übersetzung dient nur deiner Bequemlichkeit. Maßgeblich ist die <a href="/terms?lang=en">englische Fassung</a>.</p>
    <p class="muted">Zuletzt aktualisiert am {{ \Illuminate\Support\Carbon::parse('2026-10-30')->locale('de')->translatedFormat('j. F Y') }}</p>
    <p>Wenn du {{ config('portal.name') }} nutzt, stimmst du diesen Bedingungen zu. Wenn du nicht einverstanden bist, nutze die Website bitte nicht.</p>

    <h2>Die Spiele</h2>
    <p>Die Spiele sind kostenlos und werden so bereitgestellt, wie sie sind. Wir können jederzeit Spiele, Punktestände oder Funktionen hinzufügen, ändern oder entfernen, und wir können nicht versprechen, dass die Website oder ein Spiel immer verfügbar ist.</p>

    <h2>Dein Konto</h2>
    <ul>
        <li>Behalte deine Anmeldedaten für dich; du bist für die Aktivitäten in deinem Konto verantwortlich.</li>
        <li>Wähle einen Anzeigenamen, der nicht beleidigend oder irreführend ist und nicht jemand anderem gehört. Solche Namen können wir ändern oder entfernen.</li>
    </ul>

    <h2>Fair Play</h2>
    <p>Schummle nicht, manipuliere keine Punktestände, automatisiere das Spielen nicht und störe weder die Website noch andere Spieler. Wir können Punktestände entfernen, Bestenlisten zurücksetzen oder Konten sperren, die das tun.</p>

    <h2>Datenschutz</h2>
    <p>Wie wir mit deinen Daten umgehen, steht in der <a href="{{ route('privacy') }}">Datenschutzerklärung</a>.</p>

    <h2>Haftung</h2>
    <p>Soweit gesetzlich zulässig, haften wir nicht für Schäden, die aus deiner Nutzung der Website oder der Spiele entstehen.</p>

    <h2>Kontakt</h2>
    <p><a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a></p>
</article>
@endsection
