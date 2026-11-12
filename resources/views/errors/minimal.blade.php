<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') · NikniQ</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="/css/site.css">
    <script>try { var t = localStorage.getItem('theme'); if (t === 'light' || t === 'dark') document.documentElement.setAttribute('data-theme', t); } catch (e) {}</script>
    <style>.err{max-width:34rem;margin:12vh auto;padding:0 16px;text-align:center}.err .code{font-size:4rem;font-weight:800;line-height:1;opacity:.35}.err h1{margin:.5rem 0}.err .actions{display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap;margin-top:1.5rem}</style>
</head>
<body>
<main class="err">
    <a href="/" aria-label="{{ __('NikniQ home') }}"><img src="/favicon.svg" alt="" width="56" height="56"></a>
    <div class="code">@yield('code')</div>
    <h1>@yield('title')</h1>
    <p class="muted">@yield('message')</p>
    <div class="actions">
        @yield('actions')
        <a class="btn" href="/">{{ __('Browse games') }}</a>
    </div>
</main>
</body>
</html>
