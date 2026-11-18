{{-- Screenshot gallery. $game, optional $link (main picture links there), $autoplay, $eager. --}}
@php($shots = $game->galleryUrls())
<div class="gallery" data-gallery @if(($autoplay ?? false) && count($shots) > 1) data-autoplay="6000" @endif aria-roledescription="carousel" aria-label="{{ __(':name screenshots', ['name' => $game->name]) }}">
    <div class="gallery-stage">
        @foreach($shots as $i => $src)
            @php($alt = __(':name screenshot :n of :count', ['name' => $game->name, 'n' => $i + 1, 'count' => count($shots)]))
            <figure class="gallery-slide" data-slide @if($i) hidden @endif>
                @if(! empty($link))<a href="{{ $link }}" tabindex="-1">@else<button type="button" class="gallery-zoom" data-zoom="{{ $src }}" aria-label="{{ __('View larger') }}">@endif
                <img src="{{ $src }}" alt="{{ $alt }}" width="1280" height="720" decoding="async" @if($i === 0 && ($eager ?? false)) fetchpriority="high" @else loading="lazy" @endif onerror="this.style.visibility='hidden'">
                @if(! empty($link))</a>@else</button>@endif
            </figure>
        @endforeach
        @if(count($shots) > 1)
            <button type="button" class="gallery-nav prev" data-prev aria-label="{{ __('Previous screenshot') }}">‹</button>
            <button type="button" class="gallery-nav next" data-next aria-label="{{ __('Next screenshot') }}">›</button>
            <span class="gallery-count" aria-live="polite"><span data-current>1</span> / {{ count($shots) }}</span>
        @endif
    </div>
    @if(count($shots) > 1)
        <div class="gallery-thumbs" role="tablist">
            @foreach($shots as $i => $src)
                <button type="button" role="tab" data-thumb="{{ $i }}" aria-selected="{{ $i ? 'false' : 'true' }}" aria-label="{{ __('Screenshot :n', ['n' => $i + 1]) }}"><img src="{{ $src }}" alt="" loading="lazy" width="160" height="90"></button>
            @endforeach
        </div>
    @endif
</div>
@once
<dialog class="gallery-lightbox" id="gallery-lightbox" aria-label="{{ __('Screenshot') }}"><form method="dialog"><button class="gallery-close" aria-label="{{ __('Close') }}">×</button></form><img alt=""></dialog>
<script>
(function () {
  var calm = function () { return document.documentElement.dataset.calm === '1' || matchMedia('(prefers-reduced-motion: reduce)').matches; };
  document.querySelectorAll('[data-gallery]').forEach(function (g) {
    var slides = g.querySelectorAll('[data-slide]'), thumbs = g.querySelectorAll('[data-thumb]'), cur = g.querySelector('[data-current]'), i = 0, timer = null;
    if (slides.length < 2) return;
    function show(n) {
      i = (n + slides.length) % slides.length;
      slides.forEach(function (s, k) { s.hidden = k !== i; });
      thumbs.forEach(function (t, k) { t.setAttribute('aria-selected', k === i ? 'true' : 'false'); });
      if (thumbs[i]) thumbs[i].scrollIntoView({ block: 'nearest', inline: 'nearest' });
      if (cur) cur.textContent = i + 1;
    }
    g.querySelector('[data-prev]').addEventListener('click', function () { stop(); show(i - 1); });
    g.querySelector('[data-next]').addEventListener('click', function () { stop(); show(i + 1); });
    thumbs.forEach(function (t) { t.addEventListener('click', function () { stop(); show(+t.dataset.thumb); }); });
    g.addEventListener('keydown', function (e) { if (e.key === 'ArrowLeft') { stop(); show(i - 1); } if (e.key === 'ArrowRight') { stop(); show(i + 1); } });
    var ms = +g.dataset.autoplay || 0;
    function stop() { clearInterval(timer); timer = null; ms = 0; }
    if (ms && !calm()) {
      timer = setInterval(function () { if (!document.hidden) show(i + 1); }, ms);
      g.addEventListener('mouseenter', function () { clearInterval(timer); });
      g.addEventListener('mouseleave', function () { if (ms) timer = setInterval(function () { if (!document.hidden) show(i + 1); }, ms); });
      g.addEventListener('focusin', stop);
    }
  });
  var box = document.getElementById('gallery-lightbox');
  document.addEventListener('click', function (e) {
    var z = e.target.closest('[data-zoom]');
    if (z && box && box.showModal) { box.querySelector('img').src = z.dataset.zoom; box.querySelector('img').alt = z.querySelector('img').alt; box.showModal(); }
    else if (e.target === box) box.close();
  });
})();
</script>
@endonce
