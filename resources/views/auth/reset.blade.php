@extends('layouts.app')
@section('title', __('Choose a new password'))
@section('content')
    <h1>{{ __('Choose a new password') }}</h1>
    <form class="stack" method="post" action="{{ route('password.update') }}">@csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label>{{ __('Email') }} <input type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email"></label>
        @error('email')<div class="err">{{ $message }} <a href="{{ route('password.request') }}">{{ __('Request a new link') }}</a></div>@enderror
        <label>{{ __('New password') }} <input type="password" name="password" required autofocus autocomplete="new-password"></label>
        @error('password')<div class="err">{{ $message }}</div>@enderror
        <label>{{ __('Confirm new password') }} <input type="password" name="password_confirmation" required autocomplete="new-password"></label>
        <div><button class="btn" type="submit">{{ __('Save password and sign in') }}</button></div>
    </form>
@endsection
