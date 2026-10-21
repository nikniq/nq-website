@if($rows->isEmpty())
    <p class="muted">No scores yet. Be the first!</p>
@else
    <table>
        <thead><tr><th class="num">#</th><th>Player</th><th class="num">Best score</th><th class="num">Plays</th><th class="num">Wins</th><th>Last played</th></tr></thead>
        <tbody>
        @foreach($rows as $row)
            <tr @if(auth()->id() === $row['player']['id']) style="background:rgba(255,200,87,.08)" @endif>
                <td class="num">{{ $row['rank'] }}</td>
                <td>@if($row['player']['avatar'])<img class="avatar" src="{{ $row['player']['avatar'] }}" alt=""> @endif{{ $row['player']['name'] }}</td>
                <td class="num">{{ number_format($row['best_score']) }}</td>
                <td class="num">{{ $row['plays'] }}</td>
                <td class="num">{{ $row['wins'] }}</td>
                <td class="muted">{{ $row['last_played_at'] ? \Illuminate\Support\Carbon::parse($row['last_played_at'])->diffForHumans() : '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif
