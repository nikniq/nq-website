<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php($metaTitle = trim($__env->yieldContent('title', config('portal.name'))).' · '.config('portal.name'))
    @php($metaDescription = trim($__env->yieldContent('description', config('portal.tagline').' Retro & Arcade games on nikniq.com, one account for every game.')))
    @php($metaImage = trim($__env->yieldContent('image', url('/images/og-default.png'))))
    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ config('portal.name') }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $metaImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="theme-color" content="#0b1020" media="(prefers-color-scheme: dark)">
    <meta name="theme-color" content="#f5f7fb" media="(prefers-color-scheme: light)">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="manifest" href="/manifest.webmanifest">
    <style>
        :root { color-scheme:dark; --bg:#0b1020; --panel:#121a30; --panel-2:#182340; --line:rgba(255,255,255,.1); --text:#e6ecff; --muted:#a8b3d1; --strong:#fff; --accent:#d61f66; --accent-2:#00d1ff; --link:#00d1ff; --btn-text:#021122; --gold:#ffc857; --ok:#3ddc97; --danger:#ff6b6b; --header:linear-gradient(90deg,#0f172a,#1b1033); --input:#fff; --input-text:#0b1220; --code:#0a0f1e; --shadow:rgba(0,0,0,.6); --focus:#ffc857; }
        :root[data-theme="light"] { color-scheme:light; --bg:#f5f7fb; --panel:#ffffff; --panel-2:#eef1f7; --line:rgba(15,23,42,.12); --text:#111827; --muted:#4b5563; --strong:#0b1020; --accent:#c2185b; --accent-2:#0077b6; --link:#005f92; --btn-text:#ffffff; --gold:#9a6700; --ok:#047857; --danger:#b91c1c; --header:linear-gradient(90deg,#ffffff,#f3eefc); --input:#fff; --input-text:#111827; --code:#eef1f7; --shadow:rgba(15,23,42,.18); --focus:#7c3aed; }
        @media (prefers-color-scheme: light) { :root:not([data-theme="dark"]) { color-scheme:light; --bg:#f5f7fb; --panel:#ffffff; --panel-2:#eef1f7; --line:rgba(15,23,42,.12); --text:#111827; --muted:#4b5563; --strong:#0b1020; --accent:#c2185b; --accent-2:#0077b6; --link:#005f92; --btn-text:#ffffff; --gold:#9a6700; --ok:#047857; --danger:#b91c1c; --header:linear-gradient(90deg,#ffffff,#f3eefc); --input:#fff; --input-text:#111827; --code:#eef1f7; --shadow:rgba(15,23,42,.18); --focus:#7c3aed; } }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--bg); color:var(--text); font:16px/1.5 ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial; }
        a { color:var(--link); text-decoration:none; } a:hover { text-decoration:underline; }
        .wrap { max-width:1100px; margin:0 auto; padding:0 16px; }
        header.site { border-bottom:1px solid var(--line); background:var(--header); }
        header.site .wrap { display:flex; align-items:center; gap:1rem; min-height:60px; flex-wrap:wrap; }
        .brand { font-weight:900; letter-spacing:1px; color:var(--strong); font-size:1.2rem; }
        .brand span { color:var(--accent); }
        nav.main { display:flex; gap:.9rem; align-items:center; flex-wrap:wrap; margin-left:auto; }
        nav.main a[aria-current] { color:var(--link); font-weight:700; }
        nav.main a, nav.main button { color:var(--text); background:none; border:0; font:inherit; cursor:pointer; padding:0; }
        nav.main .pill { background:var(--accent); color:#fff; padding:.35rem .8rem; border-radius:999px; font-weight:700; }
        main { padding:1.5rem 0 3rem; }
        h1 { font-size:2rem; font-weight:900; letter-spacing:1px; margin:.2rem 0 .6rem; }
        h2 { font-size:1.25rem; margin:1.6rem 0 .6rem; }
        .sub { color:var(--muted); margin:0 0 1rem; }
        .flash { padding:.7rem 1rem; border-radius:8px; margin:0 0 1rem; background:rgba(61,220,151,.12); border:1px solid rgba(61,220,151,.4); }
        .flash.error { background:rgba(255,107,107,.12); border-color:rgba(255,107,107,.4); }
        .grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:1.1rem; }
        .card { position:relative; background:var(--panel); border:1px solid var(--line); border-radius:14px; overflow:hidden; display:flex; flex-direction:column; transition:transform .18s cubic-bezier(.2,.9,.2,1), box-shadow .18s ease, border-color .18s ease; }
        .card:hover { transform:translateY(-4px); box-shadow:0 18px 44px var(--shadow), 0 0 0 1px rgba(0,209,255,.35), 0 0 32px rgba(0,209,255,.18); border-color:rgba(0,209,255,.4); }
        .card.featured { border-color:rgba(255,59,129,.35); }
        .card.featured:hover { box-shadow:0 18px 44px var(--shadow), 0 0 0 1px rgba(255,59,129,.45), 0 0 32px rgba(255,59,129,.22); border-color:rgba(255,59,129,.5); }
        .card .thumb { position:relative; display:block; aspect-ratio:16/9; background:#05070f; overflow:hidden; }
        .card .thumb img { display:block; width:100%; height:100%; object-fit:cover; transition:transform .35s ease; }
        .card:hover .thumb img { transform:scale(1.06); }
        .card .thumb-fallback { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-size:2.6rem; color:#3b4a73; }
        .card .thumb-shade { position:absolute; inset:0; background:linear-gradient(180deg, rgba(0,0,0,0) 45%, rgba(5,7,15,.85) 100%); pointer-events:none; }
        .card .thumb-title { position:absolute; left:14px; right:14px; bottom:12px; display:flex; align-items:baseline; gap:.5rem; flex-wrap:wrap; text-shadow:0 2px 8px rgba(0,0,0,.8); }
        .card .thumb-title strong { color:#fff; font-size:1.15rem; font-weight:900; letter-spacing:.3px; }
        .card .thumb-title em { font-style:normal; font-size:.72rem; font-weight:700; letter-spacing:.6px; text-transform:uppercase; color:var(--accent-2); }
        .card .ribbon { position:absolute; top:12px; left:-32px; transform:rotate(-35deg); background:var(--accent); color:#fff; font-size:.68rem; font-weight:900; letter-spacing:1px; text-transform:uppercase; padding:.25rem 2.6rem; box-shadow:0 4px 12px rgba(0,0,0,.5); }
        .card .fav-form { position:absolute; top:10px; right:10px; z-index:2; }
        .card .fav { width:36px; height:36px; border-radius:50%; border:1px solid rgba(255,255,255,.18); background:rgba(5,7,15,.65); backdrop-filter:blur(6px); color:#fff; font-size:1.15rem; line-height:1; cursor:pointer; transition:transform .15s ease, background .15s ease, color .15s ease; }
        .card .fav:hover { transform:scale(1.12); background:rgba(5,7,15,.85); }
        .card .fav.on { color:var(--gold); border-color:rgba(255,200,87,.6); text-shadow:0 0 10px rgba(255,200,87,.8); }
        .card .body { padding:.85rem 1rem 1rem; display:flex; flex-direction:column; gap:.7rem; flex:1; }
        .card p { margin:0; color:var(--muted); font-size:.92rem; flex:1; line-height:1.45; }
        .card .actions { margin-top:auto; }
        .card .fav-count { margin-left:auto; font-size:.82rem; }
        .row { display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
        .btn { display:inline-block; background:var(--accent-2); color:var(--btn-text); padding:.45rem .8rem; border-radius:8px; font-weight:800; border:0; cursor:pointer; font:inherit; font-weight:800; }
        .btn:hover { text-decoration:none; filter:brightness(1.08); }
        .btn.ghost { background:var(--panel-2); color:var(--text); border:1px solid var(--line); }
        .btn.danger { background:var(--danger); color:#fff; }
        .btn.sm { padding:.3rem .6rem; font-size:.85rem; }
        .fav { font-size:1.1rem; } .fav.on { color:var(--gold); }
        .badge { display:inline-block; padding:.15rem .5rem; border-radius:4px; font-size:.75rem; font-weight:800; background:var(--accent); color:#fff; }
        .badge.muted { background:var(--panel-2); color:var(--muted); }
        .badge.calm { background:#1f5a4a; color:#9ff0d4; }
        .filters { display:flex; flex-direction:column; gap:.5rem; margin:0 0 1rem; }
        .chips { display:flex; flex-wrap:wrap; gap:.4rem; align-items:center; }
        .chip { display:inline-block; padding:.3rem .75rem; border-radius:999px; border:1px solid var(--line); background:var(--panel); color:var(--muted); font-size:.85rem; font-weight:700; text-decoration:none; transition:background .15s, color .15s, border-color .15s; }
        .chip:hover { color:var(--text); border-color:rgba(0,209,255,.45); text-decoration:none; }
        .chip.on { background:var(--accent-2); color:var(--btn-text); border-color:var(--accent-2); }
        .chip.pink.on { background:var(--accent); color:#fff; border-color:var(--accent); }
        .chip.gold.on { background:var(--gold); color:#2a1d00; border-color:var(--gold); }
        .chip.calm.on { background:#3ddc97; color:#032a1c; border-color:#3ddc97; }
        .sort { margin-left:auto; display:flex; gap:.5rem; align-items:center; font-size:.8rem; color:var(--muted); text-transform:uppercase; letter-spacing:.5px; }
        .sort a { color:var(--muted); font-weight:700; text-transform:none; letter-spacing:0; font-size:.85rem; }
        .sort a.on { color:var(--text); text-decoration:underline; text-decoration-color:var(--accent-2); text-underline-offset:4px; }
        .card .tag { position:absolute; left:12px; top:12px; padding:.25rem .6rem; border-radius:999px; background:rgba(3,42,28,.85); color:#9ff0d4; font-size:.7rem; font-weight:800; letter-spacing:.4px; backdrop-filter:blur(4px); }
        .card.featured .tag { top:auto; bottom:58px; }
        @media (max-width:700px){ .sort { margin-left:0; flex-wrap:wrap; } }
        .search { display:flex; gap:.5rem; margin:0 0 1.2rem; }
        .search input { flex:1; padding:.7rem .9rem; border-radius:10px; border:1px solid var(--line); background:var(--input); color:var(--input-text); font-size:1rem; }
        table { width:100%; border-collapse:collapse; background:var(--panel); border-radius:10px; overflow:hidden; }
        th, td { text-align:left; padding:.6rem .8rem; border-bottom:1px solid var(--line); font-size:.95rem; }
        th { color:var(--muted); font-weight:600; font-size:.8rem; text-transform:uppercase; letter-spacing:.5px; }
        tr:last-child td { border-bottom:0; } td.num, th.num { text-align:right; font-variant-numeric:tabular-nums; }
        .stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:.8rem; margin:0 0 1rem; }
        .stat { background:var(--panel); border:1px solid var(--line); border-radius:10px; padding:.8rem 1rem; }
        .stat .v { font-size:1.6rem; font-weight:900; color:var(--strong); } .stat .l { color:var(--muted); font-size:.8rem; text-transform:uppercase; letter-spacing:.5px; }
        form.stack { display:flex; flex-direction:column; gap:.8rem; max-width:520px; }
        label { display:flex; flex-direction:column; gap:.3rem; font-size:.9rem; color:var(--muted); }
        label.inline { flex-direction:row; align-items:center; gap:.5rem; }
        input[type=text], input[type=email], input[type=password], input[type=url], input[type=number], textarea, select { padding:.6rem .8rem; border-radius:8px; border:1px solid var(--line); background:var(--input); color:var(--input-text); font:inherit; }
        textarea { min-height:90px; }
        .err { color:var(--danger); font-size:.85rem; }
        .panel { background:var(--panel); border:1px solid var(--line); border-radius:12px; padding:1rem 1.2rem; }
        code, pre { background:var(--code); overflow-wrap:anywhere; border:1px solid var(--line); border-radius:6px; padding:.1rem .35rem; font-size:.9em; }
        pre { padding:.8rem; overflow:auto; }
        footer.site { border-top:1px solid var(--line); color:var(--muted); font-size:.85rem; padding:1rem 0; }
        .avatar { width:28px; height:28px; border-radius:50%; object-fit:cover; vertical-align:middle; }
        .muted { color:var(--muted); }
        @media (max-width:600px){ h1 { font-size:1.5rem; } }
        /* calm mode: larger text, more space, no motion, plain backgrounds */
        :root[data-calm="1"] { font-size:19px; }
        :root[data-calm="1"] body { line-height:1.65; }
        :root[data-calm="1"] .card:hover, :root[data-calm="1"] .card:hover .thumb img { transform:none; }
        :root[data-calm="1"] *, :root[data-calm="1"] *::before, :root[data-calm="1"] *::after { transition:none !important; animation:none !important; }
        :root[data-calm="1"] .btn, :root[data-calm="1"] .chip { min-height:48px; padding-left:1rem; padding-right:1rem; font-size:1rem; }
        :root[data-calm="1"] .btn.sm { min-height:44px; }
        :root[data-calm="1"] .card .thumb-shade { background:linear-gradient(180deg, rgba(0,0,0,0) 20%, rgba(5,7,15,.96) 100%); }
        :root[data-calm="1"] .card .ribbon { display:none; }
        :root[data-calm="1"] .grid { grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:1.4rem; }
        :root[data-calm="1"] header.site { background:var(--panel); }
        :root[data-calm="1"] .sort { display:none; }
        .theme-toggle[aria-pressed="true"] { background:var(--accent-2); color:var(--btn-text); }
        .share { display:flex; gap:.5rem; flex-wrap:wrap; align-items:center; }
        /* header menu (phones) */
        .menu { display:none; position:relative; }
        .menu summary { list-style:none; cursor:pointer; width:40px; height:40px; display:flex; align-items:center; justify-content:center; border:1px solid var(--line); border-radius:10px; font-size:1.2rem; }
        .menu summary::-webkit-details-marker { display:none; }
        .menu[open] summary { background:var(--panel-2); }
        .menu-panel { position:absolute; right:0; top:48px; z-index:20; min-width:220px; background:var(--panel); border:1px solid var(--line); border-radius:12px; box-shadow:0 16px 40px var(--shadow); padding:.6rem; display:flex; flex-direction:column; }
        .menu-panel a, .menu-panel button { display:flex; align-items:center; min-height:44px; padding:0 .7rem; border-radius:8px; color:var(--text); background:none; border:0; font:inherit; text-align:left; cursor:pointer; width:100%; }
        .menu-panel a:hover, .menu-panel button:hover { background:var(--panel-2); text-decoration:none; }
        .menu-panel a[aria-current] { color:var(--link); font-weight:700; }
        .flash { display:flex; gap:.6rem; align-items:flex-start; justify-content:space-between; }
        .flash-close { background:none; border:0; color:inherit; font-size:1.2rem; line-height:1; cursor:pointer; padding:0 .2rem; opacity:.7; }
        .flash-close:hover { opacity:1; }
        .steps { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:.8rem; margin:0 0 1.2rem; padding:0; list-style:none; counter-reset:step; }
        .steps li { background:var(--panel); border:1px solid var(--line); border-radius:12px; padding:1rem 1rem 1rem 3.2rem; position:relative; }
        .steps li::before { counter-increment:step; content:counter(step); position:absolute; left:1rem; top:1rem; width:1.6rem; height:1.6rem; border-radius:50%; background:var(--accent-2); color:var(--btn-text); font-weight:900; display:flex; align-items:center; justify-content:center; font-size:.9rem; }
        .steps strong { display:block; margin-bottom:.2rem; }
        .to-top { position:fixed; right:16px; bottom:16px; z-index:15; width:48px; height:48px; border-radius:50%; border:1px solid var(--line); background:var(--panel); color:var(--text); font-size:1.3rem; cursor:pointer; box-shadow:0 8px 24px var(--shadow); display:none; }
        .to-top.show { display:block; }
        footer.site { padding:1.6rem 0; }
        .footer-grid { display:grid; grid-template-columns:1fr auto; gap:1rem 2rem; align-items:start; }
        .footer-links { display:grid; grid-template-columns:repeat(2, auto); gap:.4rem 1.6rem; }
        .footer-links a { color:var(--muted); min-height:32px; display:inline-flex; align-items:center; }
        .footer-links a:hover { color:var(--text); }
        @media (max-width:640px) { .footer-grid { grid-template-columns:1fr; } }
        .prose { max-width:760px; } .prose h2 { margin-top:1.8rem; } .prose li { margin:.3rem 0; }
        /* usability */
        .game-hero { display:grid; grid-template-columns:minmax(0,1.4fr) minmax(0,1fr); gap:1.5rem; align-items:start; margin:.5rem 0 1.5rem; }
        .game-art { display:block; aspect-ratio:16/9; border-radius:14px; overflow:hidden; background:#05070f; border:1px solid var(--line); position:relative; }
        .game-art img { width:100%; height:100%; object-fit:cover; display:block; }
        .game-info { display:flex; flex-direction:column; gap:.8rem; }
        .game-info h1 { margin:0; }
        .lead { font-size:1.1rem; margin:0; }
        .btn.big { padding:.7rem 1.2rem; font-size:1.05rem; min-height:48px; }
        .btn.ghost.on { color:var(--gold); border-color:var(--gold); }
        @media (max-width:800px) { .game-hero { grid-template-columns:1fr; } }
        :root[data-theme="light"] .chip.gold.on, :root[data-theme="light"] .chip.calm.on { color:#fff; }
        :root[data-theme="light"] .badge.calm, :root[data-theme="light"] .card .tag { background:#d1fae5; color:#065f46; }
        :root[data-theme="light"] .card .fav { background:rgba(255,255,255,.85); color:#111827; border-color:rgba(15,23,42,.2); }
        :root[data-theme="light"] .card .fav.on { color:#9a6700; text-shadow:none; }
        .skip { position:absolute; left:-9999px; top:8px; background:var(--panel); color:var(--text); padding:.6rem 1rem; border-radius:8px; z-index:10; }
        .skip:focus { left:16px; }
        :focus-visible { outline:3px solid var(--focus); outline-offset:3px; border-radius:6px; }
        .card .thumb:focus-visible { outline-offset:-4px; }
        .card:focus-within { border-color:var(--focus); }
        .btn, .chip, nav.main a, nav.main button { min-height:40px; display:inline-flex; align-items:center; }
        .btn.sm { min-height:36px; }
        .btn:active, .chip:active { transform:translateY(1px); }
        .card .thumb-shade { background:linear-gradient(180deg, rgba(0,0,0,0) 35%, rgba(5,7,15,.92) 100%); }
        .theme-toggle { width:40px; height:40px; justify-content:center; border-radius:50% !important; border:1px solid var(--line) !important; font-size:1.1rem; }
        .table-scroll { overflow-x:auto; -webkit-overflow-scrolling:touch; border-radius:10px; }
        @media (max-width:640px) {
            header.site .wrap { gap:.5rem; padding-top:.4rem; padding-bottom:.4rem; flex-wrap:nowrap; }
            nav.main { gap:.5rem; font-size:.95rem; }
            nav.main .nav-link { display:none !important; }
            .menu { display:block; }
            nav.main .pill { padding:.3rem .7rem; }
            .chips.genres { flex-wrap:nowrap; overflow-x:auto; scrollbar-width:none; margin:0 -16px; padding:0 16px 4px; }
            .chip { white-space:nowrap; }
            .hide-sm { display:none !important; }
            .grid { grid-template-columns:repeat(2,minmax(0,1fr)); gap:.6rem; }
            .card .body { padding:.6rem .7rem .7rem; gap:.5rem; }
            .card p { display:none; }
            .card .thumb-title strong { font-size:.95rem; }
            .card .thumb-title em { display:none; }
            .card .actions .ghost, .card .fav-count { display:none; }
            .card .btn.sm { width:100%; justify-content:center; }
            .card .ribbon { font-size:.55rem; padding:.2rem 2.2rem; left:-30px; top:8px; }
            .card .tag { font-size:.6rem; }
            .brand { font-size:1.05rem; }
            th, td { padding:.55rem .6rem; }
            h1 { font-size:1.55rem; }
        }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { transition:none !important; animation:none !important; } .card:hover, .card:hover .thumb img { transform:none; } }
    </style>
    <script>
        // Apply the saved theme before first paint to avoid a flash.
        try { var t = localStorage.getItem('theme'); if (t === 'light' || t === 'dark') document.documentElement.setAttribute('data-theme', t); if (localStorage.getItem('calm') === '1') document.documentElement.setAttribute('data-calm', '1'); } catch (e) {}
    </script>
    @if(($gaId = config('portal.ga_measurement_id')) && (app()->environment('production') || config('portal.ga_always')))
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', @json($gaId));
    </script>
    @endif
    @stack('head')
    @yield('jsonld')
</head>
<body>
<a class="skip" href="#content">Skip to content</a>
<header class="site">
    <div class="wrap">
        <a class="brand" href="{{ route('games.index') }}">NIK<span>NIQ</span> GAMES</a>
        @php($navLinks = array_values(array_filter([['games.index', 'Games'], ['hall', 'Hall of fame'], auth()->check() ? ['dashboard', 'My dashboard'] : null, auth()->check() ? ['profile', 'Profile'] : null, auth()->user()?->is_admin ? ['admin.games.index', 'Admin'] : null])))
        <nav class="main" aria-label="Main">
            @foreach($navLinks as [$route, $label])
                <a class="nav-link" href="{{ route($route) }}" @if(request()->routeIs($route === 'admin.games.index' ? 'admin.*' : $route)) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
            @auth
                <form class="nav-link" method="post" action="{{ route('logout') }}">@csrf<button type="submit">Log out</button></form>
            @else
                <a class="nav-link" href="{{ route('login') }}">Log in</a>
                <a class="pill" href="{{ route('register') }}">Sign up</a>
            @endauth
            <button type="button" class="theme-toggle" id="theme-toggle" aria-label="Switch to light mode" title="Switch theme">☀</button>
            <button type="button" class="theme-toggle" id="calm-toggle" aria-pressed="false" aria-label="Calm mode: larger text, less motion" title="Calm mode">Aa</button>
            <details class="menu">
                <summary aria-label="Menu"><span aria-hidden="true">☰</span></summary>
                <div class="menu-panel">
                    @auth<p class="muted" style="margin:0 0 .4rem;font-size:.85rem">Signed in as {{ auth()->user()->name }}</p>@endauth
                    @foreach($navLinks as [$route, $label])
                        <a href="{{ route($route) }}" @if(request()->routeIs($route === 'admin.games.index' ? 'admin.*' : $route)) aria-current="page" @endif>{{ $label }}</a>
                    @endforeach
                    @auth
                        <form method="post" action="{{ route('logout') }}">@csrf<button type="submit">Log out</button></form>
                    @else
                        <a href="{{ route('login') }}">Log in</a>
                    @endauth
                </div>
            </details>
        </nav>
    </div>
</header>
<main id="content" tabindex="-1">
    <div class="wrap">
        @auth
            @if(! auth()->user()->hasPassword() && ! auth()->user()->hasLinkedProvider() && ! request()->routeIs('register.password'))
                <div class="flash error">Your account has no password yet. <a href="{{ route('register.password') }}">Choose one now</a> so you can log in next time.</div>
            @endif
        @endauth
        @if(request()->query('missing') && request()->routeIs('games.index'))<div class="flash" role="status"><span>We couldn't find that page, so here is the games catalogue.</span><button type="button" class="flash-close" aria-label="Dismiss">×</button></div>@endif
        @if(session('status'))<div class="flash" role="status"><span>{{ session('status') }}</span><button type="button" class="flash-close" aria-label="Dismiss">×</button></div>@endif
        @if(session('error'))<div class="flash error" role="alert"><span>{{ session('error') }}</span><button type="button" class="flash-close" aria-label="Dismiss">×</button></div>@endif
        @yield('content')
    </div>
</main>
<footer class="site">
    <div class="wrap footer-grid">
        <div>
            <a class="brand" href="{{ route('games.index') }}">NIK<span>NIQ</span> GAMES</a>
            <p class="muted" style="margin:.4rem 0 0">{{ config('portal.tagline') }}</p>
        </div>
        <nav aria-label="Footer" class="footer-links">
            <a href="{{ route('games.index') }}">All games</a>
            <a href="{{ route('games.index', ['dementia' => 1]) }}">Dementia-friendly games</a>
            <a href="{{ route('hall') }}">Hall of fame</a>
            @guest<a href="{{ route('register') }}">Create an account</a>@endguest
            <a href="{{ route('privacy') }}">Privacy</a>
            <a href="{{ route('terms') }}">Terms</a>
        </nav>
        <p class="muted" style="margin:0;grid-column:1/-1">&copy; {{ date('Y') }} {{ config('portal.name') }}</p>
    </div>
</footer>
<button type="button" class="to-top" id="to-top" aria-label="Back to top">↑</button>
<script>
(function () {
    var root = document.documentElement, btn = document.getElementById('theme-toggle');
    function current() { var t = root.getAttribute('data-theme'); if (t) return t; return window.matchMedia && matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark'; }
    function sync() { var light = current() === 'light'; btn.textContent = light ? '☾' : '☀'; btn.setAttribute('aria-label', light ? 'Switch to dark mode' : 'Switch to light mode'); }
    btn.addEventListener('click', function () { var next = current() === 'light' ? 'dark' : 'light'; root.setAttribute('data-theme', next); try { localStorage.setItem('theme', next); } catch (e) {} sync(); });
    sync();

    var calm = document.getElementById('calm-toggle');
    function syncCalm() { calm.setAttribute('aria-pressed', root.getAttribute('data-calm') === '1' ? 'true' : 'false'); }
    calm.addEventListener('click', function () { var on = root.getAttribute('data-calm') !== '1'; if (on) root.setAttribute('data-calm', '1'); else root.removeAttribute('data-calm'); try { localStorage.setItem('calm', on ? '1' : '0'); } catch (e) {} syncCalm(); });
    syncCalm();

    // Dismissible messages, Escape closes the phone menu, back-to-top button,
    // and "/" focuses the catalogue search.
    document.addEventListener('click', function (e) {
        var c = e.target.closest('.flash-close'); if (c) c.closest('.flash').remove();
        var m = document.querySelector('.menu[open]'); if (m && !m.contains(e.target)) m.removeAttribute('open');
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { var m = document.querySelector('.menu[open]'); if (m) { m.removeAttribute('open'); m.querySelector('summary').focus(); } }
        var tag = (document.activeElement && document.activeElement.tagName) || '';
        if (e.key === '/' && !/INPUT|TEXTAREA|SELECT/.test(tag)) { var q = document.querySelector('input[name=q]'); if (q) { e.preventDefault(); q.focus(); } }
    });
    var top = document.getElementById('to-top');
    window.addEventListener('scroll', function () { top.classList.toggle('show', window.scrollY > 1200); }, { passive: true });
    top.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches || root.dataset.calm ? 'auto' : 'smooth' }); document.getElementById('content').focus({ preventScroll: true }); });

    // Share buttons: native share sheet where available, else copy the link.
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-share]'); if (!b) return;
        var data = { title: b.dataset.title, text: b.dataset.text, url: b.dataset.url };
        if (navigator.share) { navigator.share(data).catch(function () {}); return; }
        if (navigator.clipboard) navigator.clipboard.writeText(data.url).then(function () { var old = b.textContent; b.textContent = 'Link copied'; setTimeout(function () { b.textContent = old; }, 1600); });
    });

    // Favourite stars: toggle in place instead of reloading the page.
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.matches('form[data-fav]')) return;
        e.preventDefault();
        var token = document.querySelector('meta[name=csrf-token]').content;
        fetch(form.action, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token } })
            .then(function (r) { if (!r.ok) throw r; return r.json(); })
            .then(function (d) {
                var b = form.querySelector('button'), name = form.dataset.name;
                b.classList.toggle('on', d.favourite);
                b.setAttribute('aria-pressed', d.favourite ? 'true' : 'false');
                b.setAttribute('aria-label', (d.favourite ? 'Remove ' : 'Add ') + name + (d.favourite ? ' from favourites' : ' to favourites'));
                var icon = b.querySelector('[data-icon]'); if (icon) icon.textContent = d.favourite ? '★' : '☆';
                var label = b.querySelector('[data-label]'); if (label) label.textContent = d.favourite ? 'Favourited' : 'Favourite';
            })
            .catch(function () { form.submit(); });
    });
})();
</script>
</body>
</html>
