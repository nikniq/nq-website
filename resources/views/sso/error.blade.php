@extends('layouts.app')
@section('title', 'Sign-in failed')
@section('content')
    <h1>Game sign-in failed</h1>
    <div class="flash error">{{ $message }}</div>
    <p class="muted">The game that sent you here is not set up correctly. Go back to the game and try again, or <a href="{{ route('games.index') }}">browse the catalogue</a>.</p>
@endsection
