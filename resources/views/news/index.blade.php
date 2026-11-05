@extends('layouts.app')
@section('title', 'News')
@section('description', 'What is new on '.config('portal.name').': new features, games and improvements.')
@section('content')
<div class="page-head">
    <h1>News</h1>
    <p class="muted">What's new on {{ config('portal.name') }}: features, games and improvements.</p>
</div>
<div class="news-grid">
    @foreach($articles as $article)
        <article class="news-card">
            <img src="{{ $article->image }}" alt="" loading="{{ $loop->first ? 'eager' : 'lazy' }}" width="640" height="360">
            <div class="body">
                <time class="news-date" datetime="{{ $article->date->toDateString() }}">{{ $article->date->format('j F Y') }}</time>
                <h2><a href="{{ route('news.show', $article->slug) }}">{{ $article->title }}</a></h2>
                <p>{{ $article->summary }}</p>
            </div>
        </article>
    @endforeach
</div>
@endsection
