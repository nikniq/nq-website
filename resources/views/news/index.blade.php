@extends('layouts.app')
@section('title', __('News'))
@section('description', __('What is new on :name: new features, games and improvements.', ['name' => config('portal.name')]))
@section('content')
<div class="page-head">
    <h1>{{ __('News') }}</h1>
    <p class="muted">{{ __("What's new on :name: features, games and improvements.", ['name' => config('portal.name')]) }}</p>
</div>
<div class="news-grid">
    @foreach($articles as $article)
        <article class="news-card">
            <img src="{{ $article->image }}" alt="" loading="{{ $loop->first ? 'eager' : 'lazy' }}" width="640" height="360">
            <div class="body">
                <time class="news-date" datetime="{{ $article->date->toDateString() }}">{{ $article->date->translatedFormat('j F Y') }}</time>
                <h2><a href="{{ route('news.show', $article->slug) }}">{{ $article->title }}</a></h2>
                <p>{{ $article->summary }}</p>
            </div>
        </article>
    @endforeach
</div>
@endsection
