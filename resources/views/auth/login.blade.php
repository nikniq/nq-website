@extends('layouts.app')
@section('title', __('Log in'))
@section('content')
    <h1>{{ __('Log in') }}</h1>
    @include('auth._google')
    <form class="stack" method="post" action="{{ route('login') }}">@csrf
        <label>{{ __('Email') }} <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"></label>
        @error('email')<div class="err">{{ $message }}</div>@enderror
        @if(request('resend'))
            <div class="row"><button class="btn sm ghost" type="submit" formaction="{{ route('register.resend') }}" formnovalidate>{{ __('Resend confirmation link') }}</button></div>
        @endif
        <label>{{ __('Password') }} <input type="password" name="password" required autocomplete="current-password"></label>
        <a href="{{ route('password.request') }}" style="font-size:.9rem;align-self:flex-start">{{ __('Forgot your password?') }}</a>
        <label class="inline"><input type="checkbox" name="remember" value="1"> {{ __('Remember me') }}</label>
        <div class="row"><button class="btn" type="submit">{{ __('Log in') }}</button> <span class="muted">{!! __('New here? :link', ['link' => '<a href="'.e(route('register')).'">'.e(__('Create an account')).'</a>']) !!}</span></div>
    </form>
@endsection
