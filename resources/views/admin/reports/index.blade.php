@extends('layouts.app')
@section('title', 'Admin · Reports')
@section('content')
    @include('admin._nav')
    <div class="report-head">
        <h1>Reports</h1>
        <nav class="chips" aria-label="Time range">
            @foreach($ranges as $r)
                <a class="chip {{ $days === $r ? 'on' : '' }}" @if($days === $r) aria-current="true" @endif href="{{ route('admin.reports.index', ['days' => $r]) }}">Last {{ $r }} days</a>
            @endforeach
        </nav>
    </div>
    <p class="muted" style="margin-top:0">{{ now()->subDays($days - 1)->format('j M') }} to {{ now()->format('j M Y') }}, compared with the {{ $days }} days before. Plays and time come from games that report scores; launches are Play clicks on this site by signed-in players.</p>

    @php($labels = ['new_players' => 'New players', 'active_players' => 'Active players', 'plays' => 'Plays', 'launches' => 'Launches', 'favourites' => 'Favourites added', 'minutes' => 'Minutes played'])
    <div class="kpis">
        @foreach($labels as $key => $label)
            @php([$now, $before] = $kpis[$key])
            <div class="kpi">
                <div class="l">{{ $label }}</div>
                <div class="v">{{ number_format($now) }}</div>
                <div class="d">
                    @if($before == 0)
                        {{ $now > 0 ? 'New this period' : 'No change' }}
                    @else
                        @php($pct = round(($now - $before) / $before * 100))
                        {{ $pct > 0 ? '▲' : ($pct < 0 ? '▼' : '■') }} {{ abs($pct) }}% <span>vs {{ number_format($before) }}</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="charts">
        @include('admin.reports._bars', ['rows' => $daily, 'field' => 'active_players', 'title' => 'Active players per day', 'unit' => 'players', 'peakOnly' => true])
        @include('admin.reports._bars', ['rows' => $daily, 'field' => 'plays', 'title' => 'Plays per day', 'unit' => 'plays'])
        @include('admin.reports._bars', ['rows' => $daily, 'field' => 'launches', 'title' => 'Launches per day', 'unit' => 'launches'])
        @include('admin.reports._bars', ['rows' => $daily, 'field' => 'new_players', 'title' => 'Sign-ups per day', 'unit' => 'sign-ups'])
    </div>
    <p><a class="btn sm ghost" href="{{ route('admin.reports.export', ['report' => 'daily', 'days' => $days]) }}">Download daily CSV</a></p>

    <div class="section-head" style="margin-top:2rem">
        <h2 class="section-title">Games</h2>
        <a class="btn sm ghost" style="margin-left:auto" href="{{ route('admin.reports.export', ['report' => 'games', 'days' => $days]) }}">Download CSV</a>
    </div>
    @php($topPlays = max(1, $games->max('plays')))
    <div class="table-scroll"><table>
        <thead><tr><th>Game</th><th class="num">Plays</th><th class="num">Launches</th><th class="num">Players</th><th class="num">Minutes</th><th class="num">Best score</th><th class="num">Favourites <span class="muted">(new / total)</span></th></tr></thead>
        <tbody>
        @foreach($games as $row)
            <tr @unless($row->game->is_enabled) class="muted" @endunless>
                <td><a href="{{ route('admin.games.edit', $row->game) }}">{{ $row->game->name }}</a>@unless($row->game->is_enabled) <span class="muted">(disabled)</span>@endunless</td>
                <td class="num"><span class="inline-bar" style="--w:{{ round($row->plays / $topPlays * 100) }}%"></span>{{ number_format($row->plays) }}</td>
                <td class="num">{{ number_format($row->launches) }}</td>
                <td class="num">{{ number_format($row->players) }}</td>
                <td class="num">{{ number_format($row->minutes) }}</td>
                <td class="num">{{ $row->best_score !== null ? number_format($row->best_score) : '—' }}</td>
                <td class="num">{{ $row->favourites_added }} / {{ $row->favourites_total }}</td>
            </tr>
        @endforeach
        </tbody>
    </table></div>

    <h2 class="section-title" style="margin-top:2rem">Most active players</h2>
    @if($players->isEmpty())
        <p class="muted">No player activity in this period.</p>
    @else
        <div class="table-scroll"><table>
            <thead><tr><th>Player</th><th class="num">Events</th><th class="num">Games</th><th>Last seen</th></tr></thead>
            <tbody>
            @foreach($players as $p)
                <tr><td>{{ $p->name }}</td><td class="num">{{ number_format($p->events) }}</td><td class="num">{{ $p->games }}</td><td class="muted">{{ \Illuminate\Support\Carbon::parse($p->last_seen)->diffForHumans() }}</td></tr>
            @endforeach
            </tbody>
        </table></div>
    @endif
    <div class="chart-tip" role="tooltip" hidden></div>
@endsection
