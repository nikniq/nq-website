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
<dialog class="gallery-lightbox" id="shot-lightbox" aria-label="{{ __('Screenshot') }}">
    <form method="dialog"><button class="gallery-close" aria-label="{{ __('Close') }}">×</button></form>
    <button type="button" class="gallery-nav prev" data-lb="-1" aria-label="{{ __('Previous screenshot') }}">‹</button>
    <img alt="">
    <button type="button" class="gallery-nav next" data-lb="1" aria-label="{{ __('Next screenshot') }}">›</button>
</dialog>
<script>
(function () {
  var box = document.getElementById('shot-lightbox'), img = box.querySelector('img'), list = [], i = 0;
  function show(n) { i = (n + list.length) % list.length; img.src = list[i].dataset.zoom; img.alt = list[i].querySelector('img').alt; }
  document.addEventListener('click', function (e) {
    var t = e.target.closest('.shot-tile[data-zoom]');
    if (t && box.showModal) { list = Array.prototype.slice.call(t.parentNode.querySelectorAll('.shot-tile:not([hidden])')); show(list.indexOf(t)); box.showModal(); return; }
    var nav = e.target.closest('[data-lb]'); if (nav) { show(i + +nav.dataset.lb); return; }
    if (e.target === box) box.close();
  });
  box.addEventListener('keydown', function (e) { if (e.key === 'ArrowLeft') show(i - 1); if (e.key === 'ArrowRight') show(i + 1); });
})();
</script>
@endonce
