@extends('layouts.app')
@section('title', __('Pricing'))
@section('description', __('Play every game free. Get Plus to support new games and show a Plus badge on the leaderboards.'))
@php($money = fn ($c) => \App\Services\BillingService::money($c))
@php($plus = $plans['plus'] ?? null)
@php($m = $plus['prices']['monthly']['amount'] ?? 0)
@php($y = $plus['prices']['yearly']['amount'] ?? 0)
@php($save = ($m > 0 && $y > 0) ? (int) round(100 - $y / ($m * 12) * 100) : 0)
@php($isPlus = $user?->isPlus())
@section('content')
<div class="pricing-page">
    <div class="page-head pricing-head">
        <span class="eyebrow">{{ __('Pricing') }}</span>
        <h1>{{ __('Simple pricing') }}</h1>
        <p class="lead">{{ __('Every game is free to play. Plus is for players who want to support new games.') }}</p>
    </div>

    @if(request('cancelled'))<div class="flash">{{ __('Checkout was cancelled. You have not been charged.') }}</div>@endif
    @error('billing')<div class="flash error">{{ $message }}</div>@enderror

    <form class="plans" method="post" action="{{ route('billing.checkout') }}">@csrf
        <input type="hidden" name="plan" value="plus">
        <fieldset class="interval-toggle" aria-label="{{ __('Billing period') }}">
            <label><input type="radio" name="interval" value="monthly" checked> {{ __('Monthly') }}</label>
            <label><input type="radio" name="interval" value="yearly"> {{ __('Yearly') }} @if($save > 0)<span class="save">{{ __('Save :pct%', ['pct' => $save]) }}</span>@endif</label>
        </fieldset>

        <div class="plan-grid">
            <section class="plan">
                <h2>{{ __('Free') }}</h2>
                <p class="price"><span class="amount">{{ $money(0) }}</span></p>
                <p class="muted">{{ __('Everything you need to play.') }}</p>
                <ul class="ticks">
                    <li>{{ __('Every game, in your browser') }}</li>
                    <li>{{ __('Favourites and saved scores') }}</li>
                    <li>{{ __('Leaderboards and the hall of fame') }}</li>
                    <li>{{ __('One sign-in for every game') }}</li>
                </ul>
                @auth
                    <span class="btn ghost big block" aria-disabled="true">{{ $isPlus ? __('Included') : __('Your plan') }}</span>
                @else
                    <a class="btn ghost big block" href="{{ route('register') }}">{{ __('Create a free account') }}</a>
                @endauth
            </section>

            <section class="plan plan-plus">
                <span class="plan-flag">{{ __('Support NikniQ') }}</span>
                <h2>Plus <span class="plus-badge" aria-hidden="true">PLUS</span></h2>
                <p class="price">
                    <span class="amount when-monthly">{{ $money($m) }}</span><span class="per when-monthly"> / {{ __('month') }}</span>
                    @if($m * 12 > $y)<s class="was when-yearly" aria-hidden="true">{{ $money($m * 12) }}</s> @endif<span class="amount when-yearly">{{ $money($y) }}</span><span class="per when-yearly"> / {{ __('year') }}</span>
                </p>
                <p class="muted when-yearly">{{ __(':price a month, billed yearly', ['price' => $money((int) round($y / 12))]) }}</p>
                <p class="muted when-monthly">{{ __('Cancel any time.') }}</p>
                <div class="badge-preview" aria-hidden="true"><span class="bp-avatar">{{ mb_strtoupper(mb_substr($user?->name ?? 'P', 0, 1)) }}</span><span class="bp-name">{{ $user?->name ?? 'Player' }}</span><span class="plus-badge">PLUS</span><span class="bp-score">12,480</span></div>
                <ul class="ticks">
                    <li><strong>{{ __('Everything in Free') }}</strong></li>
                    <li>{{ __('A Plus badge next to your name on leaderboards, the hall of fame and your player page') }}</li>
                    <li>{{ __('Helps pay for new games and keeps NikniQ free for everyone') }}</li>
                    <li>{{ __('Manage or cancel any time from your profile') }}</li>
                </ul>
                @if($isPlus)
                    <a class="btn big block" href="{{ route('profile') }}#plan">{{ __('You have Plus') }} ✓</a>
                @elseif(! $enabled)
                    <button class="btn big block" type="button" disabled>{{ __('Coming soon') }}</button>
                @elseif($user)
                    <button class="btn big block" type="submit">{{ __('Get Plus') }}</button>
                    <p class="muted fine">{{ __('Secure payment by Stripe. You can cancel before the next renewal.') }}</p>
                @else
                    <a class="btn big block" href="{{ route('billing.start') }}">{{ __('Log in to get Plus') }}</a>
                    <p class="muted fine">{!! __('New here? :link first, then come back.', ['link' => '<a href="'.e(route('register')).'">'.e(__('Create a free account')).'</a>']) !!}</p>
                @endif
            </section>
        </div>
    </form>

    <ul class="trust">
        <li><span aria-hidden="true">🔒</span> {{ __('Secure checkout with Stripe') }}</li>
        <li><span aria-hidden="true">↺</span> {{ __('Cancel any time.') }}</li>
        <li><span aria-hidden="true">🎮</span> {{ __('Games stay free for everyone') }}</li>
    </ul>

    <section class="compare" aria-labelledby="compare-h">
        <h2 id="compare-h">{{ __('Compare plans') }}</h2>
        <div class="table-scroll"><table>
            <thead><tr><th scope="col"><span class="sr-only">{{ __('Plan') }}</span></th><th scope="col">{{ __('Free') }}</th><th scope="col" class="col-plus">Plus</th></tr></thead>
            <tbody>
                <tr><th scope="row">{{ __('Price') }}</th><td>{{ $money(0) }}</td><td class="col-plus"><span class="when-monthly-t">{{ $money($m) }} / {{ __('month') }}</span> · {{ $money($y) }} / {{ __('year') }}</td></tr>
                @foreach([__('Every game, in your browser'), __('Favourites and saved scores'), __('Leaderboards and the hall of fame'), __('One sign-in for every game')] as $row)
                    <tr><th scope="row">{{ $row }}</th><td><span class="yes" aria-label="✓">✓</span></td><td class="col-plus"><span class="yes" aria-label="✓">✓</span></td></tr>
                @endforeach
                <tr><th scope="row">{{ __('Plus badge') }}</th><td><span class="no" aria-label="—">—</span></td><td class="col-plus"><span class="yes" aria-label="✓">✓</span></td></tr>
                <tr><th scope="row">{{ __('Supports new games') }}</th><td><span class="no" aria-label="—">—</span></td><td class="col-plus"><span class="yes" aria-label="✓">✓</span></td></tr>
            </tbody>
        </table></div>
    </section>

    <section class="pricing-faq" aria-labelledby="faq-h">
        <h2 id="faq-h">{{ __('Questions') }}</h2>
        <details><summary>{{ __('Are the games still free?') }}</summary><p>{{ __('Yes. Every game, favourites, scores and leaderboards stay free. Plus adds a badge and supports the site.') }}</p></details>
        <details><summary>{{ __('How do I cancel?') }}</summary><p>{{ __('Go to your profile and choose Manage billing. Plus stays active until the end of the period you paid for, then stops renewing.') }}</p></details>
        <details><summary>{{ __('How is payment handled?') }}</summary><p>{{ __('Payments are processed by Stripe. NikniQ never sees or stores your card details.') }}</p></details>
        <details><summary>{{ __('What happens if I delete my account?') }}</summary><p>{{ __('Your subscription is cancelled straight away and you are not charged again.') }}</p></details>
    </section>
</div>
@endsection
