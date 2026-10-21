@extends('layouts.app')
@section('title', 'Games')
@section('content')
    <span class="badge">ARCADE</span>
    <h1>{{ config('portal.name') }}</h1>
    <p class="sub">{{ config('portal.tagline') }} @guest<a href="{{ route('register') }}">Sign up</a> to save favourites and keep your scores across every game.@endguest</p>

    <form class="search" method="get" action="{{ route('games.index') }}">
        <input type="search" name="q" value="{{ $query }}" placeholder="Search games…" aria-label="Search games">
        <button class="btn" type="submit">Search</button>
        @if($query !== '')<a class="btn ghost" href="{{ route('games.index') }}">Clear</a>@endif
    </form>

    @if($games->isEmpty())
        <p class="muted">No games match “{{ $query }}”.</p>
    @else
        <div class="grid">
            @foreach($games as $game)
                @include('games._card', ['game' => $game])
            @endforeach
        </div>
    @endif
@endsection
