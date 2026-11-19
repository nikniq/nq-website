{{-- Screenshot viewer: large picture with a scrollable thumbnail rail beside it (below on phones).
     $game; optional $even (screenshots only, no artwork), $eager. Click the picture for full screen. --}}
@php($shots = array_slice(($even ?? false) ? $game->screenshotUrls() : $game->galleryUrls(), 0, 24))
@if(count($shots))
<div class="sg {{ count($shots) > 1 ? 'has-rail' : '' }}" data-sg aria-roledescription="carousel" aria-label="{{ __(':name screenshots', ['name' => $game->name]) }}">
    <div class="sg-main">
        <button type="button" class="sg-view" data-sg-zoom aria-label="{{ __('View larger') }}">
            <img data-sg-img src="{{ $shots[0] }}" alt="{{ __(':name screenshot :n of :count', ['name' => $game->name, 'n' => 1, 'count' => count($shots)]) }}" width="1280" height="720" decoding="async" @if($eager ?? false) fetchpriority="high" @endif>
            <span class="sg-expand" aria-hidden="true">⤢</span>
        </button>
        @if(count($shots) > 1)
            <button type="button" class="sg-nav prev" data-sg-step="-1" aria-label="{{ __('Previous screenshot') }}">‹</button>
            <button type="button" class="sg-nav next" data-sg-step="1" aria-label="{{ __('Next screenshot') }}">›</button>
            <span class="sg-count" aria-live="polite"><span data-sg-cur>1</span> / {{ count($shots) }}</span>
        @endif
    </div>
    @if(count($shots) > 1)
        <div class="sg-rail" role="tablist" aria-label="{{ __('Screenshots') }}">
            @foreach($shots as $i => $src)
                <button type="button" role="tab" class="sg-thumb" data-sg-thumb="{{ $i }}" data-src="{{ $src }}" data-alt="{{ __(':name screenshot :n of :count', ['name' => $game->name, 'n' => $i + 1, 'count' => count($shots)]) }}" aria-selected="{{ $i ? 'false' : 'true' }}" aria-label="{{ __('Screenshot :n', ['n' => $i + 1]) }}" @if($i) tabindex="-1" @endif>
                    <img src="{{ $src }}" alt="" loading="lazy" width="240" height="135" onerror="this.closest('.sg-thumb').hidden=true">
                </button>
            @endforeach
        </div>
    @endif
</div>
@endif
@once
<dialog class="gallery-lightbox" id="sg-lightbox" aria-label="{{ __('Screenshot') }}">
    <form method="dialog"><button class="gallery-close" aria-label="{{ __('Close') }}">×</button></form>
    <button type="button" class="gallery-nav prev" data-lb="-1" aria-label="{{ __('Previous screenshot') }}">‹</button>
    <img alt="">
    <button type="button" class="gallery-nav next" data-lb="1" aria-label="{{ __('Next screenshot') }}">›</button>
    <span class="lb-count" aria-live="polite"></span>
</dialog>
<script>
(function () {
  var box = document.getElementById('sg-lightbox'), active = null;
  document.querySelectorAll('[data-sg]').forEach(function (g) {
    var img = g.querySelector('[data-sg-img]'), cur = g.querySelector('[data-sg-cur]'), i = 0;
    var thumbs = Array.prototype.slice.call(g.querySelectorAll('[data-sg-thumb]'));
    var rail = g.querySelector('.sg-rail');
    function list() { return thumbs.filter(function (t) { return !t.hidden; }); }
    g.go = function (n, focus) {
      var l = list(); if (!l.length) return;
      i = (n + l.length) % l.length;
      var t = l[i];
      img.classList.remove('sg-in'); void img.offsetWidth; img.classList.add('sg-in');
      img.src = t.dataset.src; img.alt = t.dataset.alt;
      thumbs.forEach(function (x) { var on = x === t; x.setAttribute('aria-selected', on); x.tabIndex = on ? 0 : -1; });
      if (cur) cur.textContent = i + 1;
      // keep the selected thumbnail in view inside the rail only (no page jump)
      if (rail) {
        var vertical = rail.scrollHeight > rail.clientHeight + 2;
        if (vertical) rail.scrollTo({ top: t.offsetTop - rail.clientHeight / 2 + t.clientHeight / 2, behavior: 'smooth' });
        else rail.scrollTo({ left: t.offsetLeft - rail.clientWidth / 2 + t.clientWidth / 2, behavior: 'smooth' });
      }
      if (focus) t.focus({ preventScroll: true });
      // warm the next picture
      var nx = l[(i + 1) % l.length]; if (nx) (new Image()).src = nx.dataset.src;
    };
    g.idx = function () { return i; };
    g.urls = function () { return list().map(function (t) { return [t.dataset.src, t.dataset.alt]; }); };
    thumbs.forEach(function (t) {
      t.addEventListener('click', function () { g.go(list().indexOf(t)); });
      t.addEventListener('mouseenter', function () { if (matchMedia('(hover:hover)').matches) (new Image()).src = t.dataset.src; });
    });
    g.querySelectorAll('[data-sg-step]').forEach(function (b) { b.addEventListener('click', function () { g.go(i + +b.dataset.sgStep); }); });
    if (rail) rail.addEventListener('keydown', function (e) {
      var k = { ArrowDown: 1, ArrowRight: 1, ArrowUp: -1, ArrowLeft: -1 }[e.key];
      if (k) { e.preventDefault(); g.go(i + k, true); }
      if (e.key === 'Home') { e.preventDefault(); g.go(0, true); }
      if (e.key === 'End') { e.preventDefault(); g.go(list().length - 1, true); }
    });
    g.querySelector('.sg-main').addEventListener('keydown', function (e) {
      if (e.key === 'ArrowLeft') { e.preventDefault(); g.go(i - 1); } if (e.key === 'ArrowRight') { e.preventDefault(); g.go(i + 1); }
    });
    // swipe on touch screens
    var x0 = null;
    g.querySelector('.sg-main').addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
    g.querySelector('.sg-main').addEventListener('touchend', function (e) {
      if (x0 === null) return; var dx = e.changedTouches[0].clientX - x0; x0 = null;
      if (Math.abs(dx) > 40 && thumbs.length > 1) { g.go(i + (dx < 0 ? 1 : -1)); g.swiped = true; setTimeout(function () { g.swiped = false; }, 300); }
    });
    g.querySelector('[data-sg-zoom]').addEventListener('click', function () {
      if (g.swiped || !box || !box.showModal) return;
      active = g; lb(0); box.showModal();
    });
  });
  if (!box) return;
  var bimg = box.querySelector('img'), bcount = box.querySelector('.lb-count');
  function lb(step) {
    var u = active.urls(); if (!u.length) { u = [[active.querySelector('[data-sg-img]').src, active.querySelector('[data-sg-img]').alt]]; }
    if (step) active.go(active.idx() + step);
    var n = u.length > 1 ? active.idx() : 0;
    bimg.src = u[n][0]; bimg.alt = u[n][1];
    bcount.textContent = u.length > 1 ? (n + 1) + ' / ' + u.length : '';
    box.querySelectorAll('[data-lb]').forEach(function (b) { b.hidden = u.length < 2; });
  }
  box.addEventListener('click', function (e) { var b = e.target.closest('[data-lb]'); if (b) lb(+b.dataset.lb); else if (e.target === box) box.close(); });
  box.addEventListener('keydown', function (e) { if (e.key === 'ArrowLeft') lb(-1); if (e.key === 'ArrowRight') lb(1); });
})();
</script>
@endonce
