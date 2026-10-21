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
        .grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:1rem; }
        .card { background:var(--panel); border:1px solid var(--line); border-radius:12px; overflow:hidden; display:flex; flex-direction:column; }
        .card .thumb { aspect-ratio:16/9; background:#000 center/cover no-repeat; display:flex; align-items:center; justify-content:center; font-size:2.5rem; color:#3b4a73; }
        .card .body { padding:.9rem 1rem 1rem; display:flex; flex-direction:column; gap:.4rem; flex:1; }
        .card h3 { margin:0; font-size:1.05rem; } .card h3 a { color:#fff; }
        .card p { margin:0; color:var(--muted); font-size:.92rem; flex:1; }
        .row { display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
        .btn { display:inline-block; background:var(--accent-2); color:#021122; padding:.45rem .8rem; border-radius:8px; font-weight:800; border:0; cursor:pointer; font:inherit; font-weight:800; }
        .btn:hover { text-decoration:none; filter:brightness(1.08); }
        .btn.ghost { background:var(--panel-2); color:var(--text); border:1px solid var(--line); }
        .btn.danger { background:var(--danger); color:#fff; }
        .btn.sm { padding:.3rem .6rem; font-size:.85rem; }
        .fav { font-size:1.1rem; } .fav.on { color:var(--gold); }
        .badge { display:inline-block; padding:.15rem .5rem; border-radius:4px; font-size:.75rem; font-weight:800; background:var(--accent); color:#fff; }
        .badge.muted { background:var(--panel-2); color:var(--muted); }
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
