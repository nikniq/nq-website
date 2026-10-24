@extends('layouts.app')
@section('title', $game->name.' leaderboard')
@section('content')
    <p><a href="{{ route('games.show', $game) }}">← {{ $game->name }}</a></p>
    <h1>{{ $game->name }} leaderboard</h1>
    <p class="sub">Ranked by best score. <a href="{{ route('games.play', $game) }}">Play {{ $game->name }}</a> to get on the board.</p>
    @include('games._leaderboard', ['rows' => $leaderboard])
@endsection
