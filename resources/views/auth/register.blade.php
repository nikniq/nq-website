@extends('layouts.app')
@section('title', 'Sign up')
@section('content')
    <h1>Create your player account</h1>
    <p class="sub">One login for every game on the portal. Your favourites, scores and activity follow you.</p>
    @include('auth._google', ['label' => 'Sign up with Google'])
    <form class="stack" method="post" action="{{ route('register') }}">@csrf
        <label>Name <input type="text" name="name" value="{{ old('name') }}" required autofocus></label>
        @error('name')<div class="err">{{ $message }}</div>@enderror
        <label>Email <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"></label>
        @error('email')<div class="err">{{ $message }}</div>@enderror
        <label>Password <input type="password" name="password" required autocomplete="new-password"></label>
        @error('password')<div class="err">{{ $message }}</div>@enderror
        <label>Confirm password <input type="password" name="password_confirmation" required autocomplete="new-password"></label>
        <div class="row"><button class="btn" type="submit">Sign up</button> <span class="muted">Already have an account? <a href="{{ route('login') }}">Log in</a></span></div>
    </form>
@endsection
