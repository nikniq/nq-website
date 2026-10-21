@extends('layouts.app')
@section('title', 'Check your email')
@section('content')
    <h1>Check your email</h1>
    <div class="panel" style="max-width:560px">
        <p>If <strong>{{ $email }}</strong> can be used to sign up, a confirmation link is on its way. It works for {{ config('portal.verify_ttl_minutes', 60) }} minutes.</p>
        <p class="muted">Already registered with that address? <a href="{{ route('login') }}">Log in</a> instead.</p>
        <form method="post" action="{{ route('register.resend') }}" style="margin-top:1rem">@csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <button class="btn sm ghost" type="submit">Resend the link</button>
        </form>
    </div>
@endsection
