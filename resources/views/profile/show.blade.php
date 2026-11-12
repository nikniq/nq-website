@extends('layouts.app')
@section('title', __('Profile'))
@section('content')
    <h1>{{ __('Profile') }}</h1>
    <nav class="jump" aria-label="{{ __('Profile sections') }}">
        <a href="#details">{{ __('Details') }}</a><a href="#visibility">{{ __('Player page') }}</a><a href="#password">{{ __('Password') }}</a>@if(config('social.providers.google.enabled'))<a href="#google">Google</a>@endif<a href="#games">{{ __('Connected games') }}</a><a href="#data">{{ __('Your data') }}</a><a href="#delete">{{ __('Delete account') }}</a>
    </nav>

    <div class="panel" id="details" style="margin-bottom:1rem">
        <h2 style="margin-top:0">{{ __('Details') }}</h2>
        <form class="stack" method="post" action="{{ route('profile.update') }}">@csrf @method('PUT')
            <label>{{ __('Name') }} <input type="text" name="name" value="{{ old('name', $user->name) }}" required></label>
            @error('name')<div class="err">{{ $message }}</div>@enderror
            <label>{{ __('Email') }} <input type="email" name="email" value="{{ old('email', $user->email) }}" required></label>
            @error('email')<div class="err">{{ $message }}</div>@enderror
            <div><button class="btn" type="submit">{{ __('Save') }}</button></div>
        </form>
    </div>

    <div class="panel" id="visibility" style="margin-bottom:1rem">
        <h2 style="margin-top:0">{{ __('Public player page') }}</h2>
        <p class="muted">{!! __('Your name, best scores and favourites at :link.', ['link' => '<a href="'.e(route('players.show', $user)).'">'.e(route('players.show', $user)).'</a>']) !!} {{ __('Hidden pages are also left out of the hall of fame.') }}</p>
        <form method="post" action="{{ route('profile.visibility') }}">@csrf @method('PUT')
            <input type="hidden" name="is_public" value="{{ $user->is_public ? 0 : 1 }}">
            <button class="btn sm ghost" type="submit">{{ $user->is_public ? __('Hide my player page') : __('Make my player page public') }}</button>
        </form>
    </div>

    <div class="panel" id="password" style="margin-bottom:1rem">
        <h2 style="margin-top:0">{{ $user->hasPassword() ? __('Change password') : __('Set a password') }}</h2>
        @unless($user->hasPassword())<p class="muted">{{ $user->hasLinkedProvider() ? __('You signed up with :provider. Setting a password lets you log in without it too.', ['provider' => $user->providerLabel()]) : __('Set a password so you can log in with your email.') }}</p>@endunless
        <form class="stack" method="post" action="{{ route('profile.password') }}">@csrf @method('PUT')
            @if($user->hasPassword())
            <label>{{ __('Current password') }} <input type="password" name="current_password" required autocomplete="current-password"></label>
            @error('current_password')<div class="err">{{ $message }}</div>@enderror
            @endif
            <label>{{ __('New password') }} <input type="password" name="password" required autocomplete="new-password"></label>
            @error('password')<div class="err">{{ $message }}</div>@enderror
            <label>{{ __('Confirm new password') }} <input type="password" name="password_confirmation" required autocomplete="new-password"></label>
            <div><button class="btn" type="submit">{{ __('Change password') }}</button></div>
        </form>
    </div>

    @if(config('social.providers.google.enabled'))
    <div class="panel" id="google" style="margin-bottom:1rem">
        <h2 style="margin-top:0">{{ __('Google sign-in') }}</h2>
        @error('provider')<div class="flash error">{{ $message }}</div>@enderror
        @if($user->hasLinkedProvider())
            <p>{{ __('Linked to your :provider account.', ['provider' => $user->providerLabel()]) }} @if($user->avatar)<img class="avatar" src="{{ $user->avatar }}" alt="">@endif</p>
            <form method="post" action="{{ route('social.unlink') }}">@csrf<button class="btn sm ghost" type="submit" @unless($user->hasPassword()) disabled title="{{ __('Set a password first') }}" @endunless>{{ __('Unlink Google') }}</button></form>
            @unless($user->hasPassword())<p class="muted" style="font-size:.85rem">{{ __('Set a password above before unlinking, or you will be locked out.') }}</p>@endunless
        @else
            <p class="muted">{{ __('Link Google to sign in with one click.') }}</p>
            @include('auth._google', ['label' => __('Link Google account')])
        @endif
    </div>
    @endif

    <div class="panel" id="games">
        <h2 style="margin-top:0">{{ __('Connected games') }}</h2>
        <p class="muted">{{ __('Games currently signed in with your account.') }} {{ __('Signing out revokes the game\'s access immediately.') }}</p>
        @if($connected->isEmpty())
            <p class="muted">{{ __('No games are signed in right now.') }}</p>
        @else
            <table>
                <thead><tr><th>{{ __('Game') }}</th><th>{{ __('Connected') }}</th><th>{{ __('Last active') }}</th><th></th></tr></thead>
                <tbody>
                @foreach($connected as $row)
                    <tr>
                        <td><a href="{{ route('games.show', $row['game']) }}">{{ $row['game']->name }}</a></td>
                        <td class="muted">{{ $row['connected_at']?->diffForHumans() }}</td>
                        <td class="muted">{{ $row['last_active_at']?->diffForHumans() ?? __('never') }}</td>
                        <td><form method="post" action="{{ route('profile.disconnect') }}">@csrf<input type="hidden" name="game_id" value="{{ $row['game']->id }}"><button class="btn sm ghost" type="submit">{{ __('Sign out') }}</button></form></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <form method="post" action="{{ route('profile.disconnect') }}" style="margin-top:.8rem">@csrf<button class="btn sm danger" type="submit">{{ __('Sign out of all games') }}</button></form>
        @endif
    </div>

    <div class="panel" id="data" style="margin-top:1rem">
        <h2 style="margin-top:0">{{ __('Your data') }}</h2>
        <p class="muted">{{ __('Download a copy of everything we hold about you: account details, favourites, stats and every game event.') }}</p>
        <a class="btn sm ghost" href="{{ route('profile.export') }}">{{ __('Download my data (JSON)') }}</a>
    </div>

    <div class="panel" id="delete" style="margin-top:1rem;border-color:rgba(255,107,107,.45)">
        <h2 style="margin-top:0">{{ __('Delete account') }}</h2>
        <p class="muted">{{ __('This permanently deletes your account, favourites, scores and history, and signs you out of every game.') }} {{ __('It cannot be undone.') }}</p>
        <details>
            <summary class="btn sm danger" style="list-style:none;cursor:pointer;display:inline-flex">{{ __('Delete my account…') }}</summary>
            <form class="stack" method="post" action="{{ route('profile.destroy') }}" style="margin-top:.8rem">@csrf @method('DELETE')
                <label>{!! __('Type :email to confirm', ['email' => '<strong>'.e($user->email).'</strong>']) !!} <input type="text" name="confirm_email" autocomplete="off" required></label>
                @error('confirm_email')<div class="err">{{ $message }}</div>@enderror
                <div><button class="btn danger" type="submit">{{ __('Permanently delete my account') }}</button></div>
            </form>
        </details>
    </div>
@endsection
