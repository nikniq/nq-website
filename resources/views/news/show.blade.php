@extends('layouts.app')
@section('title', $article->title)
@section('description', $article->summary)
@section('image', url($article->image))
@section('og_type', 'article')
@section('jsonld')<script type="application/ld+json">{!! json_encode(['@@context' => 'https://schema.org', '@type' => 'NewsArticle', 'headline' => $article->title, 'datePublished' => $article->date->toDateString(), 'image' => url($article->image), 'description' => $article->summary, 'publisher' => ['@type' => 'Organization', 'name' => config('portal.name')]], JSON_UNESCAPED_SLASHES) !!}</script>@endsection
@section('content')
<p><a href="{{ route('news.index') }}">← {{ __('All news') }}</a></p>
<article class="prose news-lead">
    <time class="news-date" datetime="{{ $article->date->toDateString() }}">{{ $article->date->translatedFormat('j F Y') }}</time>
    <h1>{{ $article->title }}</h1>
    <p class="lede">{{ $article->summary }}</p>
    <img src="{{ $article->image }}" alt="" width="760" height="333">
    @include('news.articles.'.$article->slug)
</article>
@if($more->isNotEmpty())
    <h2 class="section-title" style="margin-top:2.5rem">{{ __('More news') }}</h2>
    <div class="news-grid">
        @foreach($more as $other)
            <article class="news-card">
                <img src="{{ $other->image }}" alt="" loading="lazy" width="640" height="360">
                <div class="body">
                    <time class="news-date" datetime="{{ $other->date->toDateString() }}">{{ $other->date->translatedFormat('j F Y') }}</time>
                    <h2><a href="{{ route('news.show', $other->slug) }}">{{ $other->title }}</a></h2>
                </div>
            </article>
        @endforeach
    </div>
@endif
@endsection
