@if($rows->isEmpty())
    <p class="muted">No scores yet. Be the first!</p>
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
