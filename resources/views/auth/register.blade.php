@extends('layouts.app')
@section('title', __('Sign up'))
@section('content')
    <h1>{{ __('Create your player account') }}</h1>
    <p class="sub">{{ __('One login for every game on the portal.') }} {{ __('Your favourites, scores and activity follow you.') }}</p>
    @include('auth._google', ['label' => __('Sign up with Google')])
    <form class="stack" method="post" action="{{ route('register') }}">@csrf
        <label>{{ __('Email') }} <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="you@example.com"></label>
        @error('email')<div class="err">{{ $message }}</div>@enderror
        <p class="muted" style="margin:0;font-size:.9rem">{{ __("We'll email you a link to confirm your address.") }} {{ __('You choose a password after that.') }} {!! __('By signing up you agree to the :terms and :privacy.', ['terms' => '<a href="'.e(route('terms')).'">'.e(__('terms')).'</a>', 'privacy' => '<a href="'.e(route('privacy')).'">'.e(__('privacy policy')).'</a>']) !!}</p>
        <div class="row"><button class="btn" type="submit">{{ __('Send me the link') }}</button> <span class="muted">{!! __('Already have an account? :link', ['link' => '<a href="'.e(route('login')).'">'.e(__('Log in')).'</a>']) !!}</span></div>
    </form>
@endsection
