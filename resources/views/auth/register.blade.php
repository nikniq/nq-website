@extends('layouts.app')
@section('title', 'Sign up')
@section('content')
    <h1>Create your player account</h1>
    <p class="sub">One login for every game on the portal. Your favourites, scores and activity follow you.</p>
    @include('auth._google', ['label' => 'Sign up with Google'])
    <form class="stack" method="post" action="{{ route('register') }}">@csrf
        <label>Email <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="you@example.com"></label>
        @error('email')<div class="err">{{ $message }}</div>@enderror
        <p class="muted" style="margin:0;font-size:.9rem">We'll email you a link to confirm your address. You choose a password after that. By signing up you agree to the <a href="{{ route('terms') }}">terms</a> and <a href="{{ route('privacy') }}">privacy policy</a>.</p>
        <div class="row"><button class="btn" type="submit">Send me the link</button> <span class="muted">Already have an account? <a href="{{ route('login') }}">Log in</a></span></div>
    </form>
@endsection
