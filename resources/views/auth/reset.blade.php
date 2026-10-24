@extends('layouts.app')
@section('title', 'Choose a new password')
@section('content')
    <h1>Choose a new password</h1>
    <form class="stack" method="post" action="{{ route('password.update') }}">@csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label>Email <input type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email"></label>
        @error('email')<div class="err">{{ $message }} <a href="{{ route('password.request') }}">Request a new link</a></div>@enderror
        <label>New password <input type="password" name="password" required autofocus autocomplete="new-password"></label>
        @error('password')<div class="err">{{ $message }}</div>@enderror
        <label>Confirm new password <input type="password" name="password_confirmation" required autocomplete="new-password"></label>
        <div><button class="btn" type="submit">Save password and sign in</button></div>
    </form>
@endsection
