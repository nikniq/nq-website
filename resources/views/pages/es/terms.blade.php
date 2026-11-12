@extends('layouts.app')
@section('title', 'Condiciones de uso')
@section('description', 'Las normas para usar '.config('portal.name').' y sus juegos.')
@section('content')
<article class="prose">
    <h1>Condiciones de uso</h1>
    <p class="muted">Esta es una traducción para tu comodidad. La versión que se aplica es la <a href="/terms?lang=en">versión en inglés</a>.</p>
    <p class="muted">Última actualización: {{ \Illuminate\Support\Carbon::parse('2026-10-30')->locale('es')->translatedFormat('j \d\e F \d\e Y') }}</p>
    <p>Al usar {{ config('portal.name') }} aceptas estas condiciones. Si no estás de acuerdo, por favor no uses el sitio.</p>

    <h2>Los juegos</h2>
    <p>Los juegos son gratis y se ofrecen tal cual. Podemos añadir, cambiar o quitar juegos, puntuaciones o funciones en cualquier momento, y no podemos prometer que el sitio o un juego estén siempre disponibles.</p>

    <h2>Tu cuenta</h2>
    <ul>
        <li>No compartas tus datos de acceso; eres responsable de la actividad de tu cuenta.</li>
        <li>Elige un nombre visible que no sea ofensivo, engañoso ni de otra persona. Podemos cambiar o quitar los nombres que lo sean.</li>
    </ul>

    <h2>Juego limpio</h2>
    <p>No hagas trampas, no manipules las puntuaciones, no automatices el juego ni interfieras con el sitio o con otros jugadores. Podemos quitar puntuaciones, reiniciar clasificaciones o suspender las cuentas que lo hagan.</p>

    <h2>Privacidad</h2>
    <p>Cómo tratamos tu información se explica en la <a href="{{ route('privacy') }}">política de privacidad</a>.</p>

    <h2>Responsabilidad</h2>
    <p>En la medida en que la ley lo permita, no somos responsables de ninguna pérdida derivada del uso del sitio o de los juegos.</p>

    <h2>Contacto</h2>
    <p><a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a></p>
</article>
@endsection
