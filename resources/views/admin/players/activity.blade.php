@extends('layouts.app')
@section('title', 'Admin · Activity')
@section('content')
    @include('admin._nav')
    <h1>Recent activity</h1>
    <div class="table-scroll"><table>
        <thead><tr><th>When</th><th>Player</th><th>Game</th><th>Event</th><th class="num">Score</th><th>Level</th><th>Result</th></tr></thead>
        <tbody>
        @foreach($events as $e)
            <tr>
                <td class="muted" title="{{ $e->occurred_at }}">{{ $e->occurred_at->diffForHumans() }}</td>
                <td>{{ $e->user?->name ?? '—' }}</td>
                <td>{{ $e->game?->name ?? '—' }}</td>
                <td><code>{{ $e->event }}</code></td>
                <td class="num">{{ $e->score !== null ? number_format($e->score) : '' }}</td>
                <td>{{ $e->level }}</td>
                <td>@if($e->won === true)Won @elseif($e->won === false)Lost @endif</td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    <div style="margin-top:1rem">{{ $events->links() }}</div>
@endsection
