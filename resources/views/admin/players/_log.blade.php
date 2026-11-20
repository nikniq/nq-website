{{-- Audit log rows. Needs: $logs, $showSubject (bool). --}}
<div class="table-scroll"><table class="audit-log">
    <thead><tr><th>When</th><th>What</th>@if($showSubject)<th>Player</th>@endif<th>By</th><th>Details</th><th>IP</th></tr></thead>
    <tbody>
    @forelse($logs as $log)
        <tr>
            <td class="muted" title="{{ $log->created_at }}">{{ $log->created_at?->diffForHumans() }}</td>
            <td><span @class(['log-tag', 'log-'.$log->action])>{{ \App\Support\Audit::label($log->action) }}</span></td>
            @if($showSubject)
                <td>@if($log->subject)<a href="{{ route('admin.players.show', $log->subject->id) }}">{{ $log->subject->name }}</a><div class="muted sub-line">{{ $log->subject->email }}</div>@else<span class="muted">{{ $log->subject_label ?? '—' }}</span>@endif</td>
            @endif
            <td>{{ $log->actor_id && $log->actor_id === $log->subject_id ? 'self' : ($log->actor?->name ?? '—') }}</td>
            <td class="log-details">
                @foreach((array) $log->details as $k => $v)
                    <span><strong>{{ $k }}</strong>
                    @if(is_array($v) && array_key_exists('from', $v)){{ var_export($v['from'], true) }} → {{ var_export($v['to'], true) }}@else{{ is_scalar($v) ? $v : json_encode($v) }}@endif</span>
                @endforeach
            </td>
            <td class="muted"><code>{{ $log->ip }}</code></td>
        </tr>
    @empty
        <tr><td colspan="{{ $showSubject ? 6 : 5 }}" class="muted">Nothing logged yet.</td></tr>
    @endforelse
    </tbody>
</table></div>
