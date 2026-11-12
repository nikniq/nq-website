@extends('layouts.app')
@section('title', __('Sign-in failed'))
@section('content')
    <h1>{{ __('Game sign-in failed') }}</h1>
    <div class="flash error">{{ $message }}</div>
    <p class="muted">{{ __('The game that sent you here is not set up correctly.') }} {!! __('Go back to the game and try again, or :link.', ['link' => '<a href="'.e(route('games.index')).'">'.e(__('browse the catalogue')).'</a>']) !!}</p>
@endsection
