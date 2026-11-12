@extends('layouts.app')
@section('title', 'Datenschutzerklärung')
@section('description', 'Was '.config('portal.name').' erfasst, warum, und wie du es löschen lassen kannst.')
@section('content')
<article class="prose">
    <h1>Datenschutzerklärung</h1>
    <p class="muted">Diese Übersetzung dient nur deiner Bequemlichkeit. Maßgeblich ist die <a href="/privacy?lang=en">englische Fassung</a>.</p>
    <p class="muted">Zuletzt aktualisiert am {{ \Illuminate\Support\Carbon::parse('2026-10-30')->locale('de')->translatedFormat('j. F Y') }}</p>
    <p>{{ config('portal.name') }} ({{ parse_url(config('app.url'), PHP_URL_HOST) }}) ist ein Portal für kostenlose Browserspiele. Diese Seite erklärt, was wir erfassen und warum. Du kannst jedes Spiel ohne Konto spielen; ein Konto bringt nur Favoriten, gespeicherte Punktestände und Bestenlisten dazu.</p>

    <h2>Was wir erfassen</h2>
    <ul>
        <li><strong>Kontodaten:</strong> deine E-Mail-Adresse, deinen Anzeigenamen und, falls du eines festlegst, ein Passwort (nur als Einweg-Hash gespeichert).</li>
        <li><strong>Anmeldung mit Google:</strong> Wenn du dich mit Google anmeldest, erhalten wir deinen Namen, deine E-Mail-Adresse, deine Google-Konto-ID und dein Profilfoto. Dein Google-Passwort oder Zugriff auf irgendetwas anderes erhalten wir nicht.</li>
        <li><strong>Spielaktivität:</strong> Wenn du ein Spiel angemeldet mit deinem Konto spielst, sendet uns das Spiel Ereignisse wie Punktestände, Level, Siege und Spielzeit. Wir speichern sie, um dein Dashboard, deine Ränge und die Bestenlisten zu erstellen.</li>
        <li><strong>Favoriten,</strong> die du auswählst, und die Spiele, die du über das Portal startest.</li>
        <li><strong>Technische Daten:</strong> deine IP-Adresse und Angaben zu deinem Browser in den Server-Logs, aufbewahrt für Sicherheit und Fehlersuche.</li>
    </ul>

    <h2>Was öffentlich ist</h2>
    <p>Dein Anzeigename, dein Profilfoto, deine besten Punktestände und deine Lieblingsspiele erscheinen in Bestenlisten, in der Ruhmeshalle und auf deiner Spielerseite. Deine Spielerseite kannst du in deinem <a href="{{ route('profile') }}">Profil</a> verbergen (damit verschwindest du auch aus der Ruhmeshalle). Deine E-Mail-Adresse wird nie öffentlich angezeigt.</p>

    <h2>Cookies und lokaler Speicher</h2>
    <ul>
        <li>Ein Sitzungs-Cookie hält dich angemeldet, dazu ein „Angemeldet bleiben“-Cookie, wenn du das möchtest.</li>
        <li>Deine Einstellungen für helles/dunkles Design und den Ruhemodus werden nur in deinem Browser gespeichert.</li>
        <li>Wir verwenden Google Analytics, um zu verstehen, welche Seiten genutzt werden. Es setzt eigene Cookies, und Google verarbeitet diese Daten nach seiner eigenen Datenschutzerklärung.</li>
    </ul>

    <h2>An wen wir Daten weitergeben</h2>
    <p>Wir verkaufen deine Daten nicht. Spiele, bei denen du dich über das Portal anmeldest, erhalten deinen Namen, deine E-Mail-Adresse, dein Foto und deine Statistiken für dieses Spiel. Unser Hosting-Anbieter, unser E-Mail-Anbieter und Google (Anmeldung und Analyse) verarbeiten Daten in unserem Auftrag.</p>

    <h2>Deine Möglichkeiten</h2>
    <ul>
        <li>In deinem <a href="{{ route('profile') }}">Profil</a> kannst du Namen, E-Mail-Adresse oder Passwort ändern, deine Spielerseite verbergen und dich von einzelnen Spielen abmelden.</li>
        <li>In deinem <a href="{{ route('profile') }}">Profil</a> kannst du außerdem eine Kopie all deiner Daten herunterladen oder dein Konto mit allem, was dazugehört, endgültig löschen. Du kannst uns auch eine E-Mail an <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a> schreiben.</li>
    </ul>

    <h2>Kontakt</h2>
    <p>Fragen zu dieser Erklärung: <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>.</p>
</article>
@endsection
