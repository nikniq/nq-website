@php($webp = $game->webpUrl())
<picture>
    @if($webp)<source srcset="{{ $webp }}" type="image/webp">@endif
    <img src="{{ $game->thumbnailUrl() }}" alt="{{ $alt ?? '' }}" width="640" height="360" @if($eager ?? false) fetchpriority="high" @else loading="lazy" @endif decoding="async">
</picture>
