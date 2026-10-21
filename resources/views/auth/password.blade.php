@extends('layouts.app')
@section('title', 'Choose a password')
@section('content')
    <h1>Email confirmed</h1>
    <p class="sub">Last step: pick a display name and a password for <strong>{{ $user->email }}</strong>.</p>
    <form class="stack" method="post" action="{{ route('register.password') }}">@csrf @method('PUT')
        <label>Display name (shown on leaderboards) <input type="text" name="name" value="{{ old('name', $user->name) }}" required autofocus maxlength="255"></label>
        @error('name')<div class="err">{{ $message }}</div>@enderror
        <label>Password <input type="password" name="password" required autocomplete="new-password"></label>
        @error('password')<div class="err">{{ $message }}</div>@enderror
        <label>Confirm password <input type="password" name="password_confirmation" required autocomplete="new-password"></label>
        <div class="row"><button class="btn" type="submit">Finish sign-up</button></div>
    </form>
@endsection
