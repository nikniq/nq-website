@extends('layouts.app')
@section('title', $game->name.' leaderboard')
@section('content')
    <p><a href="{{ route('games.show', $game) }}">← {{ $game->name }}</a></p>
    <h1>{{ $game->name }} leaderboard</h1>
    <p class="sub">Ranked by best score. Also available as JSON at <code>{{ route('api.games.leaderboard', $game) }}</code>.</p>
    @include('games._leaderboard', ['rows' => $leaderboard])
@endsection
