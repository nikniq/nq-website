@extends('layouts.app')
@section('title', 'Log in')
@section('content')
    <h1>Log in</h1>
    @include('auth._google')
    <form class="stack" method="post" action="{{ route('login') }}">@csrf
        <label>Email <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"></label>
        @error('email')<div class="err">{{ $message }}</div>@enderror
        @if(request('resend'))
            <div class="row"><button class="btn sm ghost" type="submit" formaction="{{ route('register.resend') }}" formnovalidate>Resend confirmation link</button></div>
        @endif
        <label>Password <input type="password" name="password" required autocomplete="current-password"></label>
        <label class="inline"><input type="checkbox" name="remember" value="1"> Remember me</label>
        <div class="row"><button class="btn" type="submit">Log in</button> <span class="muted">New here? <a href="{{ route('register') }}">Create an account</a></span></div>
    </form>
@endsection
