<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('portal.name')) · {{ config('portal.name') }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <style>
        :root { --bg:#0b1020; --panel:#121a30; --panel-2:#182340; --line:rgba(255,255,255,.08); --text:#e6ecff; --muted:#9aa7c7; --accent:#ff3b81; --accent-2:#00d1ff; --gold:#ffc857; --ok:#3ddc97; --danger:#ff6b6b; }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--bg); color:var(--text); font:16px/1.5 ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial; }
        a { color:var(--accent-2); text-decoration:none; } a:hover { text-decoration:underline; }
        .wrap { max-width:1100px; margin:0 auto; padding:0 16px; }
        header.site { border-bottom:1px solid var(--line); background:linear-gradient(90deg,#0f172a,#1b1033); }
        header.site .wrap { display:flex; align-items:center; gap:1rem; min-height:60px; flex-wrap:wrap; }
        .brand { font-weight:900; letter-spacing:1px; color:#fff; font-size:1.2rem; }
        .brand span { color:var(--accent); }
        nav.main { display:flex; gap:.9rem; align-items:center; flex-wrap:wrap; margin-left:auto; }
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
        .card:hover { transform:translateY(-4px); box-shadow:0 18px 44px rgba(0,0,0,.6), 0 0 0 1px rgba(0,209,255,.35), 0 0 32px rgba(0,209,255,.18); border-color:rgba(0,209,255,.4); }
        .card.featured { border-color:rgba(255,59,129,.35); }
        .card.featured:hover { box-shadow:0 18px 44px rgba(0,0,0,.6), 0 0 0 1px rgba(255,59,129,.45), 0 0 32px rgba(255,59,129,.22); border-color:rgba(255,59,129,.5); }
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
        .btn { display:inline-block; background:var(--accent-2); color:#021122; padding:.45rem .8rem; border-radius:8px; font-weight:800; border:0; cursor:pointer; font:inherit; font-weight:800; }
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
        .chip.on { background:var(--accent-2); color:#021122; border-color:var(--accent-2); }
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
        .search input { flex:1; padding:.7rem .9rem; border-radius:10px; border:1px solid var(--line); background:#fff; color:#0b1220; font-size:1rem; }
        table { width:100%; border-collapse:collapse; background:var(--panel); border-radius:10px; overflow:hidden; }
        th, td { text-align:left; padding:.6rem .8rem; border-bottom:1px solid var(--line); font-size:.95rem; }
        th { color:var(--muted); font-weight:600; font-size:.8rem; text-transform:uppercase; letter-spacing:.5px; }
        tr:last-child td { border-bottom:0; } td.num, th.num { text-align:right; font-variant-numeric:tabular-nums; }
        .stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:.8rem; margin:0 0 1rem; }
        .stat { background:var(--panel); border:1px solid var(--line); border-radius:10px; padding:.8rem 1rem; }
        .stat .v { font-size:1.6rem; font-weight:900; color:#fff; } .stat .l { color:var(--muted); font-size:.8rem; text-transform:uppercase; letter-spacing:.5px; }
        form.stack { display:flex; flex-direction:column; gap:.8rem; max-width:520px; }
        label { display:flex; flex-direction:column; gap:.3rem; font-size:.9rem; color:var(--muted); }
        label.inline { flex-direction:row; align-items:center; gap:.5rem; }
        input[type=text], input[type=email], input[type=password], input[type=url], input[type=number], textarea, select { padding:.6rem .8rem; border-radius:8px; border:1px solid var(--line); background:#fff; color:#0b1220; font:inherit; }
        textarea { min-height:90px; }
        .err { color:var(--danger); font-size:.85rem; }
        .panel { background:var(--panel); border:1px solid var(--line); border-radius:12px; padding:1rem 1.2rem; }
        code, pre { background:#0a0f1e; border:1px solid var(--line); border-radius:6px; padding:.1rem .35rem; font-size:.9em; }
        pre { padding:.8rem; overflow:auto; }
        footer.site { border-top:1px solid var(--line); color:var(--muted); font-size:.85rem; padding:1rem 0; }
        .avatar { width:28px; height:28px; border-radius:50%; object-fit:cover; vertical-align:middle; }
        .muted { color:var(--muted); }
        @media (max-width:600px){ h1 { font-size:1.5rem; } }
    </style>
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
</head>
<body>
<header class="site">
    <div class="wrap">
        <a class="brand" href="{{ route('games.index') }}">NIK<span>NIQ</span> GAMES</a>
        <nav class="main">
            <a href="{{ route('games.index') }}">Games</a>
            @auth
                <a href="{{ route('dashboard') }}">My dashboard</a>
                <a href="{{ route('profile') }}">{{ auth()->user()->name }}</a>
                @if(auth()->user()->is_admin)<a href="{{ route('admin.games.index') }}">Admin</a>@endif
                <form method="post" action="{{ route('logout') }}">@csrf<button type="submit">Log out</button></form>
            @else
                <a href="{{ route('login') }}">Log in</a>
                <a class="pill" href="{{ route('register') }}">Sign up</a>
            @endauth
        </nav>
    </div>
</header>
<main>
    <div class="wrap">
        @auth
            @if(! auth()->user()->hasPassword() && ! auth()->user()->hasLinkedProvider() && ! request()->routeIs('register.password'))
                <div class="flash error">Your account has no password yet. <a href="{{ route('register.password') }}">Choose one now</a> so you can log in next time.</div>
            @endif
        @endauth
        @if(session('status'))<div class="flash">{{ session('status') }}</div>@endif
        @if(session('error'))<div class="flash error">{{ session('error') }}</div>@endif
        @yield('content')
    </div>
</main>
<footer class="site"><div class="wrap">&copy; {{ date('Y') }} {{ config('portal.name') }} · {{ config('portal.tagline') }}</div></footer>
</body>
</html>
