@extends('layouts.app')
@section('title', 'Admin · '.$player->name)
@section('content')
    @include('admin._nav')
    <p><a href="{{ route('admin.players.index', $player->trashed() ? ['show' => 'deleted'] : []) }}">← Players</a></p>

    <div class="player-head">
        <span class="avatar-lg" aria-hidden="true">{{ mb_strtoupper(mb_substr($player->name, 0, 1)) }}</span>
        <div>
            <h1 style="margin:0">{{ $player->name }}
                @if($player->is_admin)<span class="badge">admin</span>@endif
                @if($player->isPlus())<span class="plus-badge">PLUS</span>@endif
                @if($player->trashed())<span class="badge warn">suspended</span>@endif
            </h1>
            <p class="muted" style="margin:.2rem 0 0">{{ $player->email }} · joined {{ $player->created_at?->toFormattedDateString() }}
                · last login {{ $player->last_login_at?->diffForHumans() ?? 'never recorded' }}@if($player->last_login_ip) from <code>{{ $player->last_login_ip }}</code>@endif</p>
        </div>
        <div class="player-head-actions">
            @if($canImpersonate)
                <form method="post" action="{{ route('admin.players.impersonate', $player->id) }}">@csrf
                    <button class="btn" type="submit" title="Opens their dashboard, profile and catalogue as they see them. Read-only.">👁 View site as {{ \Illuminate\Support\Str::limit($player->name, 18) }}</button>
                </form>
            @endif
            @if($player->is_public && ! $player->trashed())<a class="btn ghost" href="{{ route('players.show', $player) }}" target="_blank" rel="noopener">Public profile ↗</a>@endif
        </div>
    </div>

    <div class="kpis">
        <div class="kpi"><div class="l">Games played</div><div class="v">{{ $summary['games'] }}</div></div>
        <div class="kpi"><div class="l">Plays</div><div class="v">{{ number_format($summary['plays']) }}</div></div>
        <div class="kpi"><div class="l">Wins</div><div class="v">{{ number_format($summary['wins']) }}</div></div>
        <div class="kpi"><div class="l">Total score</div><div class="v">{{ number_format($summary['total_score']) }}</div></div>
        <div class="kpi"><div class="l">Time played</div><div class="v">{{ \Carbon\CarbonInterval::seconds($summary['total_duration_seconds'])->cascade()->forHumans(['short' => true, 'parts' => 2]) ?: '0s' }}</div></div>
        <div class="kpi"><div class="l">Favourites</div><div class="v">{{ $favourites->count() }}</div></div>
    </div>

    <section aria-labelledby="usage-h">
        <div class="admin-toolbar">
            <h2 id="usage-h" style="margin:0">Usage</h2>
            <nav class="chips" aria-label="Period">
                @foreach([7, 30, 90] as $d)<a class="chip {{ $days === $d ? 'on' : '' }}" href="{{ route('admin.players.show', ['id' => $player->id, 'days' => $d]) }}#usage-h">{{ $d }} days</a>@endforeach
            </nav>
        </div>
        <div class="charts">
            @include('admin.reports._bars', ['rows' => $daily, 'field' => 'plays', 'title' => 'Plays per day', 'unit' => 'plays'])
            @include('admin.reports._bars', ['rows' => $daily, 'field' => 'minutes', 'title' => 'Minutes played per day', 'unit' => 'minutes'])
            @include('admin.reports._bars', ['rows' => $daily, 'field' => 'launches', 'title' => 'Launches per day', 'unit' => 'launches'])
        </div>
    </section>

    <div class="admin-cols">
        <section aria-labelledby="edit-h" class="panel-box">
            <h2 id="edit-h">Edit details</h2>
            <form method="post" action="{{ route('admin.players.update', $player->id) }}" class="stack">@csrf @method('PUT')
                <label>Name <input type="text" name="name" value="{{ old('name', $player->name) }}" required maxlength="255"></label>
                @error('name')<div class="field-error">{{ $message }}</div>@enderror
                <label>Email <input type="email" name="email" value="{{ old('email', $player->email) }}" required maxlength="255"></label>
                @error('email')<div class="field-error">{{ $message }}</div>@enderror
                <label class="check"><input type="hidden" name="email_verified" value="0"><input type="checkbox" name="email_verified" value="1" @checked($player->email_verified_at)> Email verified @if($player->email_verified_at)<span class="muted">({{ $player->email_verified_at->toFormattedDateString() }})</span>@endif</label>
                <label class="check"><input type="hidden" name="is_public" value="0"><input type="checkbox" name="is_public" value="1" @checked($player->is_public)> Public profile and Hall of Fame listing</label>
                <p class="muted" style="margin:0;font-size:.85rem">Sign-in: {{ $player->hasLinkedProvider() ? $player->providerLabel() : '' }}{{ $player->hasLinkedProvider() && $player->hasPassword() ? ' + ' : '' }}{{ $player->hasPassword() ? 'password' : ($player->hasLinkedProvider() ? '' : 'unfinished sign-up (no password)') }}.
                    Plan: {{ $player->isPlus() ? 'Plus until '.$player->plus_until->toFormattedDateString() : 'Free' }}@if($subscription) · Stripe {{ $subscription->status }}@endif.</p>
                <div><button class="btn" type="submit">Save changes</button></div>
            </form>
        </section>

        <section aria-labelledby="sessions-h" class="panel-box">
            <h2 id="sessions-h">Game sign-ins</h2>
            @if($tokens->isEmpty())
                <p class="muted">Not signed in to any game.</p>
            @else
                <ul class="plain-list">
                    @foreach($tokens as $t)<li><strong>{{ $t->game?->name ?? 'Unknown game' }}</strong> <span class="muted">· last used {{ $t->last_used_at?->diffForHumans() ?? 'never' }} · expires {{ $t->expires_at?->toFormattedDateString() }}</span></li>@endforeach
                </ul>
                <form method="post" action="{{ route('admin.players.revoke-tokens', $player->id) }}">@csrf
                    <button class="btn sm ghost" type="submit">Sign out of all games</button>
                </form>
            @endif

            <h2 style="margin-top:1.4rem">Danger zone</h2>
            @if($player->is(auth()->user()))
                <p class="muted" style="font-size:.9rem">This is your account. Use Profile to change or delete it.</p>
            @else
                <div class="stack-sm">
                    @if($player->trashed())
                        <form method="post" action="{{ route('admin.players.restore', $player->id) }}">@csrf<button class="btn sm" type="submit">Restore account</button></form>
                    @else
                        <form method="post" action="{{ route('admin.players.admin', $player->id) }}">@csrf<button class="btn sm ghost" type="submit">{{ $player->is_admin ? 'Remove admin' : 'Make admin' }}</button></form>
                        <form method="post" action="{{ route('admin.players.destroy', $player->id) }}" class="stack-sm">@csrf @method('DELETE')
                            <label>Reason (optional) <input type="text" name="reason" maxlength="255" placeholder="e.g. spam"></label>
                            <button class="btn sm warn" type="submit">Suspend (soft delete)</button>
                            <small class="muted">Hides the account and signs it out everywhere. Can be restored.</small>
                        </form>
                    @endif
                    <form method="post" action="{{ route('admin.players.purge', $player->id) }}" class="stack-sm danger-box" onsubmit="return confirm('Permanently delete {{ $player->email }} and all of their data? This cannot be undone.')">@csrf @method('DELETE')
                        <label><span>Type <strong>{{ $player->email }}</strong> to confirm</span> <input type="text" name="confirm_email" required autocomplete="off"></label>
                        <button class="btn sm danger" type="submit">Delete permanently</button>
                    </form>
                </div>
            @endif
        </section>
    </div>

    <section aria-labelledby="pov-h" class="pov">
        <h2 id="pov-h">What {{ $player->name }} sees</h2>
        <p class="muted">Their dashboard, as rendered for them. @if($canImpersonate)Use “View site as” above to browse every page in their shoes.@endif</p>
        <div class="pov-frame">
            <div class="pov-bar" aria-hidden="true"><i></i><i></i><i></i><span>{{ parse_url(url('/dashboard'), PHP_URL_HOST) }}/dashboard</span></div>
            <div class="pov-body">
                <h3 style="margin-top:0">Hi, {{ $player->name }}</h3>
                @if($gameStats->isEmpty())
                    <p class="muted">No games played yet: they see the “Get started” steps and popular games.</p>
                @else
                    <div class="table-scroll"><table>
                        <thead><tr><th>Game</th><th class="num">Best</th><th class="num">Rank</th><th class="num">Plays</th><th class="num">Wins</th><th>Last level</th><th>Last played</th></tr></thead>
                        <tbody>
                        @foreach($gameStats as $s)
                            <tr>
                                <td><a href="{{ route('games.show', $s->game) }}">{{ $s->game->name }}</a></td>
                                <td class="num">{{ number_format($s->best_score ?? 0) }}</td>
                                <td class="num">{{ ($ranks[$s->game_id] ?? null) ? '#'.$ranks[$s->game_id] : '—' }}</td>
                                <td class="num">{{ $s->plays }}</td>
                                <td class="num">{{ $s->wins }}</td>
                                <td>{{ $s->last_level }}</td>
                                <td class="muted">{{ $s->last_played_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table></div>
                @endif
                <p style="margin-bottom:0"><strong>Favourites:</strong> @forelse($favourites as $f)<a class="chip" href="{{ route('games.show', $f) }}">★ {{ $f->name }}</a> @empty<span class="muted">none</span>@endforelse</p>
            </div>
        </div>
    </section>

    <section aria-labelledby="events-h">
        <h2 id="events-h">Recent game events</h2>
        <div class="table-scroll"><table>
            <thead><tr><th>When</th><th>Game</th><th>Event</th><th class="num">Score</th><th>Level</th><th>Result</th></tr></thead>
            <tbody>
            @forelse($events as $e)
                <tr><td class="muted" title="{{ $e->occurred_at }}">{{ $e->occurred_at->diffForHumans() }}</td><td>{{ $e->game?->name ?? '—' }}</td><td><code>{{ $e->event }}</code></td>
                    <td class="num">{{ $e->score !== null ? number_format($e->score) : '' }}</td><td>{{ $e->level }}</td><td>@if($e->won === true)Won @elseif($e->won === false)Lost @endif</td></tr>
            @empty
                <tr><td colspan="6" class="muted">No game events.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>

    <section aria-labelledby="log-h">
        <h2 id="log-h">Account log <a class="btn sm ghost" style="margin-left:.5rem;vertical-align:middle" href="{{ route('admin.logs.index', ['q' => $player->email]) }}">all entries →</a></h2>
        @include('admin.players._log', ['logs' => $logs, 'showSubject' => false])
    </section>
@endsection
