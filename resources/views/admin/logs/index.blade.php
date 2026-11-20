@extends('layouts.app')
@section('title', 'Admin · Logs')
@section('content')
    @include('admin._nav')
    <h1>Logs</h1>
    <p class="muted">Sign-ins, failed logins and every admin action on player accounts. Game scores are under Activity.</p>

    <div class="admin-toolbar">
        <nav class="chips" aria-label="Period">
            @foreach([7, 30, 90] as $d)<a class="chip {{ $days === $d ? 'on' : '' }}" href="{{ route('admin.logs.index', array_filter(['days' => $d, 'action' => $action, 'q' => $q ?: null])) }}">{{ $d }} days</a>@endforeach
        </nav>
    </div>

    <div class="kpis">
        <div class="kpi"><div class="l">Logins</div><div class="v">{{ number_format($totals['login'] ?? 0) }}</div><div class="d">{{ number_format($uniqueLogins) }} <span>players</span></div></div>
        <div class="kpi"><div class="l">Failed logins</div><div class="v">{{ number_format($totals['login_failed'] ?? 0) }}</div></div>
        <div class="kpi"><div class="l">Admin actions</div><div class="v">{{ number_format($daily->sum('admin')) }}</div></div>
        <div class="kpi"><div class="l">Suspended / deleted</div><div class="v">{{ number_format(($totals['player_suspended'] ?? 0) + ($totals['player_deleted'] ?? 0)) }}</div></div>
    </div>

    <div class="charts">
        @include('admin.reports._bars', ['rows' => $daily, 'field' => 'logins', 'title' => 'Logins per day', 'unit' => 'logins'])
        @include('admin.reports._bars', ['rows' => $daily, 'field' => 'failed', 'title' => 'Failed logins per day', 'unit' => 'attempts'])
        @include('admin.reports._bars', ['rows' => $daily, 'field' => 'admin', 'title' => 'Admin actions per day', 'unit' => 'actions'])
    </div>

    <form class="admin-toolbar" method="get">
        <input type="hidden" name="days" value="{{ $days }}">
        <input type="search" name="q" value="{{ $q }}" placeholder="Email or IP" aria-label="Filter by email or IP" style="flex:1 1 240px">
        <select name="action" aria-label="Action">
            <option value="">All actions</option>
            @foreach(\App\Support\Audit::ACTIONS as $key => $label)<option value="{{ $key }}" @selected($action === $key)>{{ $label }}@if(isset($totals[$key])) ({{ $totals[$key] }})@endif</option>@endforeach
        </select>
        <button class="btn" type="submit">Filter</button>
        @if($q || $action)<a class="btn ghost" href="{{ route('admin.logs.index', ['days' => $days]) }}">Clear</a>@endif
    </form>

    @include('admin.players._log', ['logs' => $logs, 'showSubject' => true])
    <div style="margin-top:1rem">{{ $logs->links() }}</div>
@endsection
