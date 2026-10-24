@extends('layouts.app')
@section('title', 'Forgot password')
@section('content')
    <h1>Forgot your password?</h1>
    <p class="sub">Enter your email and we'll send you a link to choose a new one.</p>
    <form class="stack" method="post" action="{{ route('password.email') }}">@csrf
        <label>Email <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"></label>
        @error('email')<div class="err">{{ $message }}</div>@enderror
        <div class="row"><button class="btn" type="submit">Send reset link</button> <a href="{{ route('login') }}">Back to log in</a></div>
    </form>
@endsection
