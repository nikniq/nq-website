@extends('layouts.app')
@section('title', 'Profile')
@section('content')
    <h1>Profile</h1>

    <div class="panel" style="margin-bottom:1rem">
        <h2 style="margin-top:0">Details</h2>
        <form class="stack" method="post" action="{{ route('profile.update') }}">@csrf @method('PUT')
            <label>Name <input type="text" name="name" value="{{ old('name', $user->name) }}" required></label>
            @error('name')<div class="err">{{ $message }}</div>@enderror
            <label>Email <input type="email" name="email" value="{{ old('email', $user->email) }}" required></label>
            @error('email')<div class="err">{{ $message }}</div>@enderror
            <div><button class="btn" type="submit">Save</button></div>
        </form>
    </div>

    <div class="panel" style="margin-bottom:1rem">
        <h2 style="margin-top:0">Public player page</h2>
        <p class="muted">Your name, best scores and favourites at <a href="{{ route('players.show', $user) }}">{{ route('players.show', $user) }}</a>. Hidden pages are also left out of the hall of fame.</p>
        <form method="post" action="{{ route('profile.visibility') }}">@csrf @method('PUT')
            <input type="hidden" name="is_public" value="{{ $user->is_public ? 0 : 1 }}">
            <button class="btn sm ghost" type="submit">{{ $user->is_public ? 'Hide my player page' : 'Make my player page public' }}</button>
        </form>
    </div>

    <div class="panel" style="margin-bottom:1rem">
        <h2 style="margin-top:0">{{ $user->hasPassword() ? 'Change password' : 'Set a password' }}</h2>
        @unless($user->hasPassword())<p class="muted">{{ $user->hasLinkedProvider() ? 'You signed up with '.$user->providerLabel().'. Setting a password lets you log in without it too.' : 'Set a password so you can log in with your email.' }}</p>@endunless
        <form class="stack" method="post" action="{{ route('profile.password') }}">@csrf @method('PUT')
            @if($user->hasPassword())
            <label>Current password <input type="password" name="current_password" required autocomplete="current-password"></label>
            @error('current_password')<div class="err">{{ $message }}</div>@enderror
            @endif
            <label>New password <input type="password" name="password" required autocomplete="new-password"></label>
            @error('password')<div class="err">{{ $message }}</div>@enderror
            <label>Confirm new password <input type="password" name="password_confirmation" required autocomplete="new-password"></label>
            <div><button class="btn" type="submit">Change password</button></div>
        </form>
    </div>

    @if(config('social.providers.google.enabled'))
    <div class="panel" style="margin-bottom:1rem">
        <h2 style="margin-top:0">Google sign-in</h2>
        @error('provider')<div class="flash error">{{ $message }}</div>@enderror
        @if($user->hasLinkedProvider())
            <p>Linked to your {{ $user->providerLabel() }} account. @if($user->avatar)<img class="avatar" src="{{ $user->avatar }}" alt="">@endif</p>
            <form method="post" action="{{ route('social.unlink') }}">@csrf<button class="btn sm ghost" type="submit" @unless($user->hasPassword()) disabled title="Set a password first" @endunless>Unlink Google</button></form>
            @unless($user->hasPassword())<p class="muted" style="font-size:.85rem">Set a password above before unlinking, or you will be locked out.</p>@endunless
        @else
            <p class="muted">Link Google to sign in with one click.</p>
            @include('auth._google', ['label' => 'Link Google account'])
        @endif
    </div>
    @endif

    <div class="panel">
        <h2 style="margin-top:0">Connected games</h2>
        <p class="muted">Games currently signed in with your account. Signing out revokes the game's access immediately.</p>
        @if($connected->isEmpty())
            <p class="muted">No games are signed in right now.</p>
        @else
            <table>
                <thead><tr><th>Game</th><th>Connected</th><th>Last active</th><th></th></tr></thead>
                <tbody>
                @foreach($connected as $row)
                    <tr>
                        <td><a href="{{ route('games.show', $row['game']) }}">{{ $row['game']->name }}</a></td>
                        <td class="muted">{{ $row['connected_at']?->diffForHumans() }}</td>
                        <td class="muted">{{ $row['last_active_at']?->diffForHumans() ?? 'never' }}</td>
                        <td><form method="post" action="{{ route('profile.disconnect') }}">@csrf<input type="hidden" name="game_id" value="{{ $row['game']->id }}"><button class="btn sm ghost" type="submit">Sign out</button></form></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <form method="post" action="{{ route('profile.disconnect') }}" style="margin-top:.8rem">@csrf<button class="btn sm danger" type="submit">Sign out of all games</button></form>
        @endif
    </div>
@endsection
