@extends('layouts.app')
@section('title', __(':name leaderboard', ['name' => $game->name]))
@section('description', __('Top players and best scores in :name on :site.', ['name' => $game->name, 'site' => config('portal.name')]))
@if($game->thumbnailUrl())@section('image', url($game->thumbnailUrl()))@endif
@section('content')
    <p><a href="{{ route('games.show', $game) }}">← {{ $game->name }}</a></p>
    <h1>{{ __(':name leaderboard', ['name' => $game->name]) }}</h1>
    <p class="sub">{{ __('Ranked by best score.') }} {!! __(':link to get on the board.', ['link' => '<a href="'.e(route('games.play', $game)).'">'.e(__('Play :name', ['name' => $game->name])).'</a>']) !!}</p>
    @include('games._leaderboard', ['rows' => $leaderboard])
@endsection
