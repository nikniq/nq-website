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
        <h2 style="margin-top:0">Change password</h2>
        <form class="stack" method="post" action="{{ route('profile.password') }}">@csrf @method('PUT')
            <label>Current password <input type="password" name="current_password" required autocomplete="current-password"></label>
            @error('current_password')<div class="err">{{ $message }}</div>@enderror
            <label>New password <input type="password" name="password" required autocomplete="new-password"></label>
            @error('password')<div class="err">{{ $message }}</div>@enderror
            <label>Confirm new password <input type="password" name="password_confirmation" required autocomplete="new-password"></label>
            <div><button class="btn" type="submit">Change password</button></div>
        </form>
    </div>

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
