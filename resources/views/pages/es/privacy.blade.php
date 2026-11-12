@extends('layouts.app')
@section('title', 'Política de privacidad')
@section('description', 'Qué datos recoge '.config('portal.name').', por qué y cómo pedir que se eliminen.')
@section('content')
<article class="prose">
    <h1>Política de privacidad</h1>
    <p class="muted">Esta es una traducción para tu comodidad. La versión que se aplica es la <a href="/privacy?lang=en">versión en inglés</a>.</p>
    <p class="muted">Última actualización: {{ \Illuminate\Support\Carbon::parse('2026-10-30')->locale('es')->translatedFormat('j \d\e F \d\e Y') }}</p>
    <p>{{ config('portal.name') }} ({{ parse_url(config('app.url'), PHP_URL_HOST) }}) es un portal de juegos gratis para el navegador. Esta página explica qué datos recogemos y por qué. Puedes jugar a todos los juegos sin cuenta; la cuenta solo añade favoritos, puntuaciones guardadas y clasificaciones.</p>

    <h2>Qué datos recogemos</h2>
    <ul>
        <li><strong>Datos de la cuenta:</strong> tu dirección de correo electrónico, tu nombre visible y, si la eliges, una contraseña (guardada solo como hash de un solo sentido).</li>
        <li><strong>Inicio de sesión con Google:</strong> si entras con Google, recibimos tu nombre, tu correo electrónico, el identificador de tu cuenta de Google y tu foto de perfil. No recibimos tu contraseña de Google ni acceso a nada más.</li>
        <li><strong>Actividad en los juegos:</strong> cuando juegas con tu cuenta iniciada, el juego nos envía eventos como puntuaciones, niveles, victorias y tiempo de juego. Los guardamos para crear tu panel, tus puestos y las clasificaciones.</li>
        <li><strong>Favoritos</strong> que eliges y los juegos que abres desde el portal.</li>
        <li><strong>Datos técnicos:</strong> tu dirección IP y los datos de tu navegador en los registros del servidor, que guardamos por seguridad y para resolver problemas.</li>
    </ul>

    <h2>Qué es público</h2>
    <p>Tu nombre visible, tu foto de perfil, tus mejores puntuaciones y tus juegos favoritos aparecen en las clasificaciones, en el salón de la fama y en tu página de jugador. Puedes ocultar tu página de jugador (lo que también te quita del salón de la fama) desde tu <a href="{{ route('profile') }}">perfil</a>. Tu dirección de correo nunca se muestra públicamente.</p>

    <h2>Cookies y almacenamiento local</h2>
    <ul>
        <li>Una cookie de sesión mantiene tu sesión iniciada, y una cookie de «recordarme» si lo pides.</li>
        <li>Tus preferencias de tema claro/oscuro y de modo tranquilo se guardan solo en tu navegador.</li>
        <li>Usamos Google Analytics para saber qué páginas se usan. Pone sus propias cookies y Google trata esos datos según su propia política de privacidad.</li>
    </ul>

    <h2>Con quién los compartimos</h2>
    <p>No vendemos tus datos. Los juegos en los que inicias sesión a través del portal reciben tu nombre, tu correo, tu foto y tus estadísticas en ese juego. Nuestro proveedor de alojamiento, nuestro proveedor de correo y Google (inicio de sesión y analítica) tratan datos en nuestro nombre.</p>

    <h2>Tus opciones</h2>
    <ul>
        <li>Cambia tu nombre, tu correo o tu contraseña, oculta tu página de jugador y cierra sesión en juegos concretos desde tu <a href="{{ route('profile') }}">perfil</a>.</li>
        <li>Descarga una copia de todos tus datos, o elimina para siempre tu cuenta y todo lo asociado a ella, desde tu <a href="{{ route('profile') }}">perfil</a>. También puedes escribirnos a <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>.</li>
    </ul>

    <h2>Contacto</h2>
    <p>Preguntas sobre esta política: <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>.</p>
</article>
@endsection
