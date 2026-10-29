@if($rows->isEmpty())
    <div class="panel" style="display:flex;gap:1rem;align-items:center;justify-content:space-between;flex-wrap:wrap">
        <p style="margin:0">No scores yet. Play signed in with your NikniQ account and your name goes to the top.</p>
        @isset($game)@if($game->url)<a class="btn sm" href="{{ route('games.play', $game) }}">▶ Be the first</a>@endif @endisset
    </div>
@else
    <div class="table-scroll"><table>
        <thead><tr><th class="num">#</th><th>Player</th><th class="num">Best score</th><th class="num hide-sm">Plays</th><th class="num hide-sm">Wins</th><th class="hide-sm">Last played</th></tr></thead>
        <tbody>
        @foreach($rows as $row)
            <tr @if(auth()->id() === $row['player']['id']) style="background:rgba(255,200,87,.08)" @endif>
                <td class="num">{{ $row['rank'] }}</td>
                <td><a href="{{ route('players.show', $row['player']['id']) }}">@if($row['player']['avatar'])<img class="avatar" src="{{ $row['player']['avatar'] }}" alt=""> @endif{{ $row['player']['name'] }}</a></td>
                <td class="num">{{ number_format($row['best_score']) }}</td>
                <td class="num hide-sm">{{ $row['plays'] }}</td>
                <td class="num hide-sm">{{ $row['wins'] }}</td>
                <td class="muted hide-sm">{{ $row['last_played_at'] ? \Illuminate\Support\Carbon::parse($row['last_played_at'])->diffForHumans() : '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
@endif
