{{-- Screenshot grid: first picture large, the rest as tiles; click opens the lightbox. $game, optional $eager, $max. --}}
@php($shots = array_slice(($even ?? false) ? $game->screenshotUrls() : $game->galleryUrls(), 0, $max ?? 12))
<div class="shot-grid {{ ($even ?? false) ? 'even' : 'shots-'.min(count($shots), 5) }}" aria-label="{{ __(':name screenshots', ['name' => $game->name]) }}" role="group">
    @foreach($shots as $i => $src)
        <button type="button" class="shot-tile" data-zoom="{{ $src }}" aria-label="{{ __('View larger') }}">
            <img src="{{ $src }}" alt="{{ __(':name screenshot :n of :count', ['name' => $game->name, 'n' => $i + 1, 'count' => count($shots)]) }}" width="640" height="360" decoding="async" @if($i === 0 && ($eager ?? false)) fetchpriority="high" @else loading="lazy" @endif onerror="this.closest('.shot-tile').hidden=true">
        </button>
    @endforeach
</div>
@once
<dialog class="shot-viewer" id="shot-lightbox" aria-label="{{ __('Screenshots') }}">
    <div class="sv-stage">
        <img alt="" data-sv-img>
        <button type="button" class="sv-nav prev" data-sv-step="-1" aria-label="{{ __('Previous screenshot') }}">‹</button>
        <button type="button" class="sv-nav next" data-sv-step="1" aria-label="{{ __('Next screenshot') }}">›</button>
        <span class="sv-count" aria-live="polite" data-sv-count></span>
    </div>
    <div class="sv-rail" role="tablist" aria-label="{{ __('Screenshots') }}" data-sv-rail></div>
    <form method="dialog"><button class="sv-close" aria-label="{{ __('Close') }}">×</button></form>
</dialog>
<script>
(function () {
  var box = document.getElementById('shot-lightbox'); if (!box || !box.showModal) return;
  var img = box.querySelector('[data-sv-img]'), rail = box.querySelector('[data-sv-rail]'), count = box.querySelector('[data-sv-count]'), list = [], thumbs = [], i = 0;
  function show(n, focus) {
    i = (n + list.length) % list.length;
    img.classList.remove('sv-in'); void img.offsetWidth; img.classList.add('sv-in');
    img.src = list[i].src; img.alt = list[i].alt;
    count.textContent = list.length > 1 ? (i + 1) + ' / ' + list.length : '';
    thumbs.forEach(function (t, k) { t.setAttribute('aria-selected', k === i); t.tabIndex = k === i ? 0 : -1; });
    var t = thumbs[i];
    if (t) {
      var vertical = getComputedStyle(rail).flexDirection === 'column';
      rail.scrollTo(vertical ? { top: t.offsetTop - rail.clientHeight / 2 + t.clientHeight / 2, behavior: 'smooth' } : { left: t.offsetLeft - rail.clientWidth / 2 + t.clientWidth / 2, behavior: 'smooth' });
      if (focus) t.focus({ preventScroll: true });
    }
    if (list.length > 1) (new Image()).src = list[(i + 1) % list.length].src;
  }
  function open(tile) {
    var tiles = Array.prototype.slice.call(tile.parentNode.querySelectorAll('.shot-tile:not([hidden])'));
    list = tiles.map(function (t) { var im = t.querySelector('img'); return { src: t.dataset.zoom, alt: im.alt }; });
    rail.innerHTML = ''; thumbs = list.map(function (it, k) {
      var b = document.createElement('button'); b.type = 'button'; b.className = 'sv-thumb'; b.setAttribute('role', 'tab');
      b.setAttribute('aria-label', it.alt); b.innerHTML = '<img alt="" loading="lazy">'; b.firstChild.src = it.src;
      b.addEventListener('click', function () { show(k); }); rail.appendChild(b); return b;
    });
    box.classList.toggle('single', list.length < 2);
    box.showModal(); show(tiles.indexOf(tile), true);
  }
  document.addEventListener('click', function (e) { var t = e.target.closest('.shot-tile[data-zoom]'); if (t) open(t); });
  box.addEventListener('click', function (e) {
    var s = e.target.closest('[data-sv-step]'); if (s) { show(i + +s.dataset.svStep); return; }
    if (e.target === box) box.close();
  });
  box.addEventListener('keydown', function (e) {
    var k = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[e.key];
    if (k) { e.preventDefault(); show(i + k, rail.contains(document.activeElement)); }
    if (e.key === 'Home') { e.preventDefault(); show(0, true); } if (e.key === 'End') { e.preventDefault(); show(list.length - 1, true); }
  });
  var x0 = null;
  box.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
  box.addEventListener('touchend', function (e) { if (x0 === null) return; var dx = e.changedTouches[0].clientX - x0; x0 = null; if (Math.abs(dx) > 40 && !rail.contains(e.target)) show(i + (dx < 0 ? 1 : -1)); });
  box.addEventListener('close', function () { img.removeAttribute('src'); });
})();
</script>
@endonce
