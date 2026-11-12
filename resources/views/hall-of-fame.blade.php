@extends('layouts.app')
@section('title', __('Hall of fame'))
@section('description', __('The best players across every game on :site, and the current champion of each game.', ['site' => config('portal.name')]))
@section('content')
    <h1>{{ __('Hall of fame') }}</h1>
    <p class="sub">{{ __('Top-three finishes in each game earn 3, 2 and 1 points. Ties go to the player with more wins.') }}</p>
    <nav class="chips" aria-label="{{ __('Period') }}" style="margin-bottom:1rem">
        @foreach(['all' => __('All time'), 'month' => __('This month'), 'week' => __('This week')] as $key => $label)
            <a class="chip {{ $period === $key ? 'on' : '' }}" @if($period === $key) aria-current="true" @endif href="{{ route('hall', $key === 'all' ? [] : ['period' => $key]) }}">{{ $label }}</a>
        @endforeach
    </nav>

    <h2>{{ __('Overall standings') }}</h2>
    @if($overall->isEmpty())
        <div class="empty">
            <p class="empty-icon" aria-hidden="true">🏆</p>
            <p><strong>{{ __('No ranked players yet.') }}</strong><br>{{ __('Play a game signed in and your best scores can put you here.') }}</p>
            <a class="btn" href="{{ route('games.index') }}">{{ __('Find a game') }}</a>
            @guest <a class="btn ghost" href="{{ route('register') }}">{{ __('Create a free account') }}</a>@endguest
        </div>
    @else
        <div class="table-scroll"><table>
            <thead><tr><th class="num">#</th><th>{{ __('Player') }}</th><th class="num">{{ __('Points') }}</th><th class="num hide-sm">{{ __('Games') }}</th><th class="num">{{ __('Wins') }}</th><th class="num hide-sm">{{ __('Plays') }}</th></tr></thead>
            <tbody>
            @foreach($overall as $i => $row)
                <tr @if(auth()->id() === $row['user']->id) style="background:rgba(255,200,87,.08)" @endif>
                    <td class="num">{{ ['🥇','🥈','🥉'][$i] ?? $i + 1 }}</td>
                    <td><a href="{{ route('players.show', $row['user']) }}">@if($row['user']->avatar)<img class="avatar" src="{{ $row['user']->avatar }}" alt=""> @endif{{ $row['user']->name }}</a></td>
                    <td class="num"><strong>{{ $row['points'] }}</strong></td>
                    <td class="num hide-sm">{{ $row['games'] }}</td>
                    <td class="num">{{ $row['wins'] }}</td>
                    <td class="num hide-sm">{{ $row['plays'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    @endif

    <h2>{{ __('Champions') }}</h2>
    @if($champions->isEmpty())
        <p class="muted">{{ __('No champions yet.') }}</p>
    @else
        <div class="grid">
            @foreach($champions as $c)
                <article class="card">
                    <a class="thumb" href="{{ route('games.show', $c['game']) }}" aria-label="{{ $c['game']->name }}">
                        @if($c['game']->thumbnailUrl())<img src="{{ $c['game']->thumbnailUrl() }}" alt="" width="640" height="360" loading="lazy">@endif
                        <span class="thumb-shade"></span>
                        <span class="thumb-title"><strong>{{ $c['game']->name }}</strong></span>
                    </a>
                    <div class="body">
                        <p>🏆 <a href="{{ route('players.show', $c['user']) }}">{{ $c['user']->name }}</a> · {{ number_format($c['stat']->best_score) }}</p>
                        <div class="row actions"><a class="btn sm ghost" href="{{ route('games.leaderboard', $c['game']) }}">{{ __('Leaderboard') }}</a></div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
@endsection
