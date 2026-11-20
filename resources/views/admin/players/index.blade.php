@extends('layouts.app')
@section('title', 'Admin · Players')
@section('content')
    @include('admin._nav')
    <h1>Players</h1>

    <div class="admin-toolbar">
        <form class="search" method="get" style="flex:1 1 320px;display:flex;gap:.6rem">
            <input type="hidden" name="show" value="{{ $show }}">
            <input type="search" name="q" value="{{ $q }}" placeholder="Search by name or email" aria-label="Search players" style="flex:1">
            <button class="btn" type="submit">Search</button>
        </form>
        <nav class="chips" aria-label="Show">
            @foreach(['active' => 'Active', 'admins' => 'Admins', 'deleted' => 'Deleted'] as $key => $label)
                <a class="chip {{ $show === $key ? 'on' : '' }}" href="{{ route('admin.players.index', array_filter(['show' => $key === 'active' ? null : $key, 'q' => $q ?: null])) }}">{{ $label }} <span class="n">{{ number_format($counts[$key]) }}</span></a>
            @endforeach
        </nav>
    </div>

    @if($show === 'deleted')
        <p class="muted">Suspended accounts can't log in, sign up again or use their game sign-ins, and are hidden from leaderboards. Their data is kept until you delete them permanently.</p>
    @endif

    <div class="table-scroll"><table class="admin-players">
        <thead><tr><th>Player</th><th>Sign-in</th><th class="num">★</th><th class="num">Events</th><th>{{ $show === 'deleted' ? 'Suspended' : 'Last active' }}</th><th class="actions-h">Actions</th></tr></thead>
        <tbody>
        @forelse($players as $u)
            <tr @class(['is-off' => $u->trashed()])>
                <td>
                    <a class="row-title" href="{{ route('admin.players.show', $u->id) }}">{{ $u->name }}</a> @if($u->is_admin)<span class="badge">admin</span>@endif @if($u->is(auth()->user()))<span class="badge calm">you</span>@endif
                    <div class="muted sub-line">{{ $u->email }} · joined {{ $u->created_at?->toFormattedDateString() }}</div>
                </td>
                <td class="muted">{{ $u->hasLinkedProvider() ? $u->providerLabel() : ($u->hasPassword() ? 'password' : 'unfinished') }}</td>
                <td class="num">{{ $u->favourite_games_count }}</td>
                <td class="num">{{ $u->game_events_count }}</td>
                <td class="muted">
                    @if($u->trashed())
                        {{ $u->deleted_at->diffForHumans() }}@if($u->deleted_reason)<div class="sub-line">“{{ $u->deleted_reason }}”</div>@endif
                    @else
                        {{ $u->game_events_max_occurred_at ? \Illuminate\Support\Carbon::parse($u->game_events_max_occurred_at)->diffForHumans() : '—' }}
                    @endif
                </td>
                <td class="actions">
                    @unless($u->is(auth()->user()))
                    <details class="row-menu">
                        <summary class="btn sm ghost">Manage ▾</summary>
                        <div class="row-menu-panel">
                            <a class="btn sm" href="{{ route('admin.players.show', $u->id) }}">Details, edit &amp; usage</a>
                            @if($u->trashed())
                                <form method="post" action="{{ route('admin.players.restore', $u->id) }}">@csrf
                                    <button class="btn sm" type="submit">Restore account</button>
                                </form>
                            @else
                                @if($u->is_public)<a class="btn sm ghost" href="{{ route('players.show', $u) }}" target="_blank" rel="noopener">Public profile ↗</a>@endif
                                <form method="post" action="{{ route('admin.players.admin', $u->id) }}">@csrf
                                    <button class="btn sm ghost" type="submit">{{ $u->is_admin ? 'Remove admin' : 'Make admin' }}</button>
                                </form>
                                <form method="post" action="{{ route('admin.players.destroy', $u->id) }}" class="stack-sm">@csrf @method('DELETE')
                                    <label>Reason (optional) <input type="text" name="reason" maxlength="255" placeholder="e.g. spam"></label>
                                    <button class="btn sm warn" type="submit">Suspend (soft delete)</button>
                                    <small class="muted">Hides the account and signs it out everywhere. Can be restored.</small>
                                </form>
                            @endif
                            <form method="post" action="{{ route('admin.players.purge', $u->id) }}?show={{ $show }}" class="stack-sm danger-box" onsubmit="return confirm('Permanently delete {{ $u->email }} and all of their scores, favourites and activity? This cannot be undone.')">@csrf @method('DELETE')
                                <label><span>Type <strong>{{ $u->email }}</strong> to confirm</span> <input type="text" name="confirm_email" required autocomplete="off"></label>
                                <button class="btn sm danger" type="submit">Delete permanently</button>
                                <small class="muted">Removes the user and all their data. Cannot be undone.</small>
                            </form>
                        </div>
                    </details>
                    @endunless
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">No players {{ $q ? 'match “'.$q.'”' : 'here' }}.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div style="margin-top:1rem">{{ $players->links() }}</div>
@endsection
