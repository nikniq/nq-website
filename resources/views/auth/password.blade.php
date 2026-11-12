@extends('layouts.app')
@section('title', __('Choose a password'))
@section('content')
    <h1>{{ __('Email confirmed') }}</h1>
    <p class="sub">{!! __('Last step: pick a display name and a password for :email.', ['email' => '<strong>'.e($user->email).'</strong>']) !!}</p>
    <form class="stack" method="post" action="{{ route('register.password') }}">@csrf @method('PUT')
        <label>{{ __('Display name (shown on leaderboards)') }} <input type="text" name="name" value="{{ old('name', $user->name) }}" required autofocus maxlength="255"></label>
        @error('name')<div class="err">{{ $message }}</div>@enderror
        <label>{{ __('Password') }} <input type="password" name="password" required autocomplete="new-password"></label>
        @error('password')<div class="err">{{ $message }}</div>@enderror
        <label>{{ __('Confirm password') }} <input type="password" name="password_confirmation" required autocomplete="new-password"></label>
        <div class="row"><button class="btn" type="submit">{{ __('Finish sign-up') }}</button></div>
    </form>
@endsection
