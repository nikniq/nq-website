@extends('layouts.app')
@section('title', 'Admin · Players')
@section('content')
    @include('admin._nav')
    <h1>Players</h1>
    <form class="search" method="get">
        <input type="search" name="q" value="{{ $q }}" placeholder="Search by name or email" aria-label="Search players">
        <button class="btn" type="submit">Search</button>
    </form>
    <p class="muted">{{ number_format($players->total()) }} players</p>
    <div class="table-scroll"><table>
        <thead><tr><th>Player</th><th>Email</th><th>Joined</th><th>Sign-in</th><th class="num">★</th><th class="num">Events</th><th>Last active</th></tr></thead>
        <tbody>
        @foreach($players as $u)
            <tr>
                <td>{{ $u->name }} @if($u->is_admin)<span class="badge">admin</span>@endif</td>
                <td class="muted">{{ $u->email }}</td>
                <td class="muted">{{ $u->created_at?->diffForHumans() }}</td>
                <td class="muted">{{ $u->hasLinkedProvider() ? $u->providerLabel() : ($u->hasPassword() ? 'password' : 'unfinished') }}</td>
                <td class="num">{{ $u->favourite_games_count }}</td>
                <td class="num">{{ $u->game_events_count }}</td>
                <td class="muted">{{ $u->game_events_max_occurred_at ? \Illuminate\Support\Carbon::parse($u->game_events_max_occurred_at)->diffForHumans() : '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    <div style="margin-top:1rem">{{ $players->links() }}</div>
@endsection
