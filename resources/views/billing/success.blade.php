@extends('layouts.app')
@section('title', __('Thank you'))
@section('content')
<div class="pricing-page" style="text-align:center;max-width:640px;margin-inline:auto">
    <p style="font-size:3rem;margin:.5rem 0">🎉</p>
    <h1>{{ __('Thank you for supporting NikniQ!') }}</h1>
    @if($user->isPlus())
        <p class="lead">{{ __('Plus is active. Your Plus badge now shows next to your name.') }}</p>
    @else
        <p class="lead">{{ __('Your payment is being confirmed. Plus switches on within a minute; refresh this page if it has not yet.') }}</p>
    @endif
    <div class="row" style="justify-content:center">
        <a class="btn big" href="{{ route('games.index') }}">▶ {{ __('Play a game') }}</a>
        <a class="btn big ghost" href="{{ route('profile') }}#plan">{{ __('Your plan') }}</a>
    </div>
</div>
@endsection
