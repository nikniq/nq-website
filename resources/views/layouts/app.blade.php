<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php($metaTitle = html_entity_decode(trim($__env->yieldContent('title', config('portal.name'))), ENT_QUOTES).' · '.config('portal.name'))
    @php($metaDescription = html_entity_decode(trim($__env->yieldContent('description', config('portal.tagline').' '.__('Retro & Arcade games on nikniq.com, one account for every game.'))), ENT_QUOTES))
    @php($metaImage = trim($__env->yieldContent('image', url('/images/og-default.png'))))
    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    @php($langUrl = fn ($code) => $code === 'en' ? url()->current().'?lang=en' : url()->current().'?lang='.$code)
    <link rel="canonical" href="{{ app()->isLocale('en') ? url()->current() : $langUrl(app()->getLocale()) }}">
    @foreach(array_keys(config('portal.locales')) as $code)<link rel="alternate" hreflang="{{ $code }}" href="{{ $langUrl($code) }}">
    @endforeach<link rel="alternate" hreflang="x-default" href="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ config('portal.name') }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ app()->isLocale('en') ? url()->current() : $langUrl(app()->getLocale()) }}">
    <meta property="og:locale" content="{{ ['en' => 'en_GB', 'ja' => 'ja_JP', 'fr' => 'fr_FR', 'de' => 'de_DE', 'es' => 'es_ES'][app()->getLocale()] ?? 'en_GB' }}">
    <meta property="og:image" content="{{ $metaImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="theme-color" content="#0b1020" media="(prefers-color-scheme: dark)">
    <meta name="theme-color" content="#f5f7fb" media="(prefers-color-scheme: light)">
    <link rel="preload" href="/fonts/plus-jakarta-sans-latin.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="stylesheet" href="/css/site.css?v={{ @filemtime(public_path('css/site.css')) }}">
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
<a class="skip" href="#content">{{ __('Skip to content') }}</a>
<header class="site">
    <div class="wrap">
        <a class="brand" href="{{ route('games.index') }}">NIK<span>NIQ</span> GAMES</a>
        @php($navLinks = array_values(array_filter([['games.index', __('Games')], ['hall', __('Hall of fame')], ['news.index', __('News')], auth()->check() ? ['dashboard', __('My dashboard')] : null, auth()->check() ? ['profile', __('Profile')] : null, auth()->user()?->is_admin ? ['admin.games.index', __('Admin')] : null])))
        <nav class="main" aria-label="{{ __('Main') }}">
            @foreach($navLinks as [$route, $label])
                <a class="nav-link" href="{{ route($route) }}" @if(request()->routeIs($route === 'admin.games.index' ? 'admin.*' : ($route === 'news.index' ? 'news.*' : $route))) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
            @auth
                <form class="nav-link" method="post" action="{{ route('logout') }}">@csrf<button type="submit">{{ __('Log out') }}</button></form>
            @else
                <a class="nav-link" href="{{ route('login') }}">{{ __('Log in') }}</a>
                @unless(request()->routeIs('register*'))<a class="pill" href="{{ route('register') }}">{{ __('Sign up') }}</a>@endunless
            @endauth
            <button type="button" class="theme-toggle" id="theme-toggle" aria-label="{{ __('Switch to light mode') }}" title="{{ __('Switch theme') }}">☀</button>
            <button type="button" class="theme-toggle" id="calm-toggle" aria-pressed="false" aria-label="{{ __('Calm mode: larger text, less motion') }}" title="{{ __('Calm mode') }}">Aa</button>
            <details class="menu">
                <summary aria-label="{{ __('Menu') }}"><span aria-hidden="true">☰</span></summary>
                <div class="menu-panel">
                    @auth<p class="muted" style="margin:0 0 .4rem;font-size:.85rem">{{ __('Signed in as :name', ['name' => auth()->user()->name]) }}</p>@endauth
                    @foreach($navLinks as [$route, $label])
                        <a href="{{ route($route) }}" @if(request()->routeIs($route === 'admin.games.index' ? 'admin.*' : ($route === 'news.index' ? 'news.*' : $route))) aria-current="page" @endif>{{ $label }}</a>
                    @endforeach
                    <button type="button" onclick="document.getElementById('theme-toggle').click()">◐&nbsp; {{ __('Light / dark mode') }}</button>
                    <button type="button" onclick="document.getElementById('calm-toggle').click()">Aa&nbsp; {{ __('Calm mode (larger text)') }}</button>
                    <nav class="lang-switch" aria-label="{{ __('Language') }}">@foreach(config('portal.locales') as $code => $name)<a href="{{ $langUrl($code) }}" hreflang="{{ $code }}" lang="{{ $code }}" @if(app()->isLocale($code)) aria-current="true" @endif>{{ $name }}</a>@endforeach</nav>
                    @auth
                        <form method="post" action="{{ route('logout') }}">@csrf<button type="submit">{{ __('Log out') }}</button></form>
                    @else
                        <a href="{{ route('login') }}">{{ __('Log in') }}</a>
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
                <div class="flash error">{!! __('Your account has no password yet. :link so you can log in next time.', ['link' => '<a href="'.e(route('register.password')).'">'.e(__('Choose one now')).'</a>']) !!}</div>
            @endif
        @endauth
        @if(request()->query('missing') && request()->routeIs('games.index'))<div class="flash" role="status"><span>{!! htmlspecialchars(__("We couldn't find that page, so here is the games catalogue."), ENT_NOQUOTES) !!}</span><button type="button" class="flash-close" aria-label="{{ __('Dismiss') }}">×</button></div>@endif
        @if(session('status'))<div class="flash" role="status"><span>{{ session('status') }}</span><button type="button" class="flash-close" aria-label="{{ __('Dismiss') }}">×</button></div>@endif
        @if(session('error'))<div class="flash error" role="alert"><span>{{ session('error') }}</span><button type="button" class="flash-close" aria-label="{{ __('Dismiss') }}">×</button></div>@endif
        @yield('content')
    </div>
</main>
<footer class="site">
    <div class="wrap footer-main">
        <div class="footer-brand">
            <a class="brand" href="{{ route('games.index') }}">NIK<span>NIQ</span> GAMES</a>
            <p>{{ config('portal.tagline') }}</p>
            @guest
                <a class="btn footer-cta" href="{{ route('register') }}">{{ __('Create a free account') }}</a>
            @else
                <a class="btn footer-cta" href="{{ route('games.random') }}">🎲&nbsp; {{ __('Surprise me') }}</a>
            @endguest
        </div>
        <nav class="footer-cols" aria-label="{{ __('Footer') }}">
            <div>
                <h2>{{ __('Play') }}</h2>
                <a href="{{ route('games.index') }}">{{ __('All games') }}</a>
                <a href="{{ route('games.index', ['dementia' => 1]) }}">{{ __('Dementia-friendly games') }}</a>
                <a href="{{ route('games.random') }}">{{ __('Surprise me') }}</a>
                <a href="{{ route('hall') }}">{{ __('Hall of fame') }}</a>
            </div>
            <div>
                <h2>NikniQ</h2>
                <a href="{{ route('news.index') }}">{{ __('News') }}</a>
                <a href="{{ route('developers') }}">{{ __('For game developers') }}</a>
            </div>
            <div>
                <h2>{{ __('Account') }}</h2>
                @auth
                    <a href="{{ route('dashboard') }}">{{ __('My dashboard') }}</a>
                    <a href="{{ route('profile') }}">{{ __('Profile') }}</a>
                @else
                    <a href="{{ route('login') }}">{{ __('Log in') }}</a>
                    <a href="{{ route('register') }}">{{ __('Create an account') }}</a>
                @endauth
            </div>
            <div>
                <h2>{{ __('Legal') }}</h2>
                <a href="{{ route('privacy') }}">{{ __('Privacy') }}</a>
                <a href="{{ route('terms') }}">{{ __('Terms') }}</a>
            </div>
        </nav>
    </div>
    <div class="footer-bottom">
        <div class="wrap">
            <p>&copy; {{ date('Y') }} {{ config('portal.name') }}</p>
            <nav class="lang-switch" aria-label="{{ __('Language') }}"><span aria-hidden="true" class="globe">🌐</span>@foreach(config('portal.locales') as $code => $name)<a href="{{ $langUrl($code) }}" hreflang="{{ $code }}" lang="{{ $code }}" @if(app()->isLocale($code)) aria-current="true" @endif>{{ $name }}</a>@endforeach</nav>
        </div>
    </div>
</footer>
<button type="button" class="to-top" id="to-top" aria-label="{{ __('Back to top') }}">↑</button>
<script>
(function () {
    var root = document.documentElement, btn = document.getElementById('theme-toggle');
    function current() { var t = root.getAttribute('data-theme'); if (t) return t; return window.matchMedia && matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark'; }
    function sync() { var light = current() === 'light'; btn.textContent = light ? '☾' : '☀'; btn.setAttribute('aria-label', light ? @json(__('Switch to dark mode')) : @json(__('Switch to light mode'))); }
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
        if (navigator.clipboard) navigator.clipboard.writeText(data.url).then(function () { var old = b.textContent; b.textContent = @json(__('Link copied')); setTimeout(function () { b.textContent = old; }, 1600); });
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
                b.setAttribute('aria-label', (d.favourite ? @json(__('Remove :name from favourites')) : @json(__('Add :name to favourites'))).replace(':name', name));
                var icon = b.querySelector('[data-icon]'); if (icon) icon.textContent = d.favourite ? '★' : '☆';
                var label = b.querySelector('[data-label]'); if (label) label.textContent = d.favourite ? @json(__('Favourited')) : @json(__('Favourite'));
            })
            .catch(function () { form.submit(); });
    });
})();

        (function () {
            // Instant catalogue search: mirrors App\Support\GameSearch (words, "phrases",
            // -exclude, genre:/name:/is:, all/any words, names only). Enter still asks
            // the server, which also ranks results and forgives typos.
            var q = document.querySelector('.searchbar input[name=q]');
            if (!q) return;
            var grid = document.querySelector('[data-live]'), form = q.form;
            var empty = document.querySelector('.live-empty'), count = document.querySelector('.section-head .count');
            var list = document.getElementById('search-suggest'), data = [], loaded = false, active = -1, items = [];
            function load() {
                if (loaded || !list || !list.dataset.src) return; loaded = true;
                fetch(list.dataset.src, { headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); })
                    .then(function (d) { data = d; if (document.activeElement === q && q.value.trim()) suggest(); }).catch(function () {});
            }
            function norm(s) { return String(s || '').replace(/[À-ɏ]/g, function (c) { return c.normalize('NFD').replace(/[̀-ͯ]/g, ''); }).toLowerCase().trim(); }
            function parse(raw) {
                var re = /(-?)(?:(\w+):)?(?:"([^"]*)"|(\S+))/g, m, out = [];
                while ((m = re.exec(raw))) {
                    var field = (m[2] || '').toLowerCase(), phrase = m[3] !== undefined && m[3] !== '', v = norm(phrase ? m[3] : (m[4] || ''));
                    if (!v || v === '-') continue;
                    var type = ['genre', 'name', 'is'].indexOf(field) !== -1 ? field : 'word';
                    if (field && type === 'word') v = norm(field + ':' + v);
                    out.push({ type: type, v: v, neg: m[1] === '-' });
                }
                return out;
            }
            function opt(name) { var el = form && form.querySelector('input[name=' + name + ']:checked'); return el ? el.value : ''; }
            function matches(card, terms) {
                var namesOnly = opt('in') === 'name', any = opt('match') === 'any';
                var text = namesOnly ? card.dataset.name + ' ' + card.dataset.genre : card.dataset.search, flags = ' ' + (card.dataset.flags || '') + ' ';
                var positives = 0, hits = 0;
                for (var i = 0; i < terms.length; i++) {
                    var t = terms[i], hit;
                    if (t.type === 'is') hit = flags.indexOf(' ' + t.v + ' ') !== -1;
                    else if (t.type === 'genre') hit = card.dataset.genre.indexOf(t.v) !== -1;
                    else if (t.type === 'name') hit = card.dataset.name.indexOf(t.v) !== -1;
                    else hit = text.indexOf(t.v) !== -1;
                    if (t.neg) { if (hit) return false; continue; }
                    positives++;
                    if (!hit && (t.type !== 'word' || !any)) return false;
                    if (hit) hits++;
                }
                return positives === 0 || hits > 0;
            }
            function filterGrid() {
                if (!grid) return;
                var terms = parse(q.value), shown = 0;
                grid.querySelectorAll('.card').forEach(function (c) { var hit = !terms.length || matches(c, terms); c.hidden = !hit; if (hit) shown++; });
                if (empty) empty.hidden = shown > 0;
                if (count) count.textContent = (shown === 1 ? @json(__(':count game')) : @json(__(':count games'))).replace(':count', shown);
            }
            function close() { if (!list) return; list.hidden = true; q.setAttribute('aria-expanded', 'false'); q.removeAttribute('aria-activedescendant'); active = -1; }
            function suggest() {
                if (!list) return;
                var raw = q.value.trim(), term = norm(raw);
                if (!term || /[":-]/.test(raw)) { close(); return; }
                var words = term.split(/\s+/);
                items = data.filter(function (g) { return words.every(function (w) { return g.s.indexOf(w) !== -1; }); })
                    .sort(function (a, b) { return (norm(a.n).indexOf(term) === 0 ? 0 : 1) - (norm(b.n).indexOf(term) === 0 ? 0 : 1); }).slice(0, 6);
                list.innerHTML = '';
                items.forEach(function (g, i) {
                    var li = document.createElement('li'); li.id = 'sg-' + i; li.setAttribute('role', 'option');
                    var a = document.createElement('a'); a.href = g.u; a.tabIndex = -1;
                    if (g.t) { var img = document.createElement('img'); img.src = g.t; img.alt = ''; img.width = 56; img.height = 32; img.loading = 'lazy'; a.appendChild(img); }
                    var n = document.createElement('span'); n.className = 'sg-name'; n.textContent = g.n; a.appendChild(n);
                    if (g.g) { var gg = document.createElement('span'); gg.className = 'sg-genre'; gg.textContent = g.g; a.appendChild(gg); }
                    li.appendChild(a); list.appendChild(li);
                });
                var all = document.createElement('li'); all.id = 'sg-' + items.length; all.setAttribute('role', 'option'); all.className = 'sg-all';
                var b = document.createElement('button'); b.type = 'submit'; b.tabIndex = -1; b.textContent = @json(__('See all results for ":q"')).replace(':q', raw); all.appendChild(b); list.appendChild(all);
                list.hidden = false; q.setAttribute('aria-expanded', 'true'); active = -1;
            }
            function move(d) {
                var opts = list.querySelectorAll('[role=option]'); if (!opts.length) return;
                if (active >= 0) opts[active].removeAttribute('aria-selected');
                active = (active + d + opts.length) % opts.length;
                opts[active].setAttribute('aria-selected', 'true'); q.setAttribute('aria-activedescendant', opts[active].id);
            }
            q.addEventListener('focus', load);
            q.addEventListener('input', function () { load(); filterGrid(); suggest(); });
            q.addEventListener('keydown', function (e) {
                if (!list || list.hidden) return;
                if (e.key === 'ArrowDown') { e.preventDefault(); move(1); }
                else if (e.key === 'ArrowUp') { e.preventDefault(); move(-1); }
                else if (e.key === 'Escape') { close(); }
                else if (e.key === 'Enter' && active >= 0) {
                    var el = list.querySelectorAll('[role=option]')[active].querySelector('a');
                    if (el) { e.preventDefault(); location.href = el.href; }
                }
            });
            q.addEventListener('blur', function () { setTimeout(close, 150); });
            if (form) form.querySelectorAll('.adv-search input').forEach(function (r) { r.addEventListener('change', filterGrid); });
        })();

        document.querySelectorAll('input[type=password]').forEach(function (input) {
            var wrap = document.createElement('span'); wrap.className = 'pw-wrap';
            input.parentNode.insertBefore(wrap, input); wrap.appendChild(input);
            var b = document.createElement('button'); b.type = 'button'; b.className = 'pw-toggle'; b.textContent = @json(__('Show'));
            b.setAttribute('aria-label', @json(__('Show password'))); b.setAttribute('aria-pressed', 'false');
            b.addEventListener('click', function () {
                var show = input.type === 'password'; input.type = show ? 'text' : 'password';
                b.textContent = show ? @json(__('Hide')) : @json(__('Show')); b.setAttribute('aria-pressed', show ? 'true' : 'false');
                b.setAttribute('aria-label', show ? @json(__('Hide password')) : @json(__('Show password'))); input.focus();
            });
            wrap.appendChild(b);
        });

        (function () {
            var tip = document.querySelector('.chart-tip'); if (!tip) return;
            function show(el) { var r = el.getBoundingClientRect(); tip.textContent = el.dataset.tip; tip.style.left = (r.left + r.width / 2) + 'px'; tip.style.top = (r.top + r.height - (el.firstElementChild.offsetHeight || 0)) + 'px'; tip.hidden = false; }
            document.querySelectorAll('.chart-bars .col').forEach(function (c) {
                c.addEventListener('mouseenter', function () { show(c); }); c.addEventListener('focus', function () { show(c); });
                c.addEventListener('mouseleave', function () { tip.hidden = true; }); c.addEventListener('blur', function () { tip.hidden = true; });
            });
            window.addEventListener('scroll', function () { tip.hidden = true; }, { passive: true });
        })();
</script>
</body>
</html>
