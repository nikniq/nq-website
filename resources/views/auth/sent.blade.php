@extends('layouts.app')
@section('title', __('Check your email'))
@section('content')
    <h1>{{ __('Check your email') }}</h1>
    <div class="panel" style="max-width:560px">
        <p>{!! __('If :email can be used to sign up, a confirmation link is on its way.', ['email' => '<strong>'.e($email).'</strong>']) !!} {{ __('It works for :minutes minutes.', ['minutes' => config('portal.verify_ttl_minutes', 60)]) }}</p>
        <p class="muted">{!! __('Already registered with that address? :link instead.', ['link' => '<a href="'.e(route('login')).'">'.e(__('Log in')).'</a>']) !!}</p>
        <form method="post" action="{{ route('register.resend') }}" style="margin-top:1rem">@csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <button class="btn sm ghost" type="submit">{{ __('Resend the link') }}</button>
        </form>
    </div>
@endsection
