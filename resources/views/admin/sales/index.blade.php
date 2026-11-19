@extends('layouts.app')
@section('title', 'Admin · Sales')
@section('content')
    @include('admin._nav')
    @php($money = fn ($c) => \App\Services\BillingService::money((int) $c, $currency))
    <div class="report-head">
        <h1>Sales</h1>
        <div class="row">
            <a class="btn ghost" href="{{ route('pricing') }}" target="_blank" rel="noopener">Pricing page ↗</a>
            <a class="btn ghost" href="{{ route('admin.sales.export') }}">Export payments (CSV)</a>
            <a class="btn ghost" href="https://dashboard.stripe.com/{{ str_starts_with($stripe['secret'], 'sk_test') ? 'test/' : '' }}payments" target="_blank" rel="noopener">Stripe dashboard ↗</a>
        </div>
    </div>

    <div class="panel stripe-status">
        <h2 style="margin-top:0">Stripe setup</h2>
        <dl class="kv">
            <dt>Status</dt><dd>@if($enabled)<span class="status on">Taking payments</span>@else<span class="status off">Off</span> <span class="muted">The pricing page shows “Coming soon”.</span>@endif</dd>
            <dt>Secret key</dt><dd>@if($stripe['secret'] === '')<span class="status off">Missing</span> set <code>STRIPE_SECRET</code>@else<span class="status on">{{ str_starts_with($stripe['secret'], 'sk_live') ? 'Live mode' : 'Test mode' }}</span>@endif</dd>
            <dt>Webhook</dt><dd>@if($stripe['webhook'])<span class="status on">Signing secret set</span>@else<span class="status off">Missing</span> set <code>STRIPE_WEBHOOK_SECRET</code>@endif
                <br><small class="muted">Endpoint: <code>{{ route('api.stripe.webhook') }}</code> · events: <code>checkout.session.completed</code>, <code>customer.subscription.created/updated/deleted</code>, <code>invoice.paid</code>, <code>charge.refunded</code></small></dd>
            @foreach($stripe['prices'] as $label => $p)
                <dt>{{ ucfirst($label) }}</dt><dd>{{ $money($p['amount']) }} · @if($p['stripe_price'])<code>{{ $p['stripe_price'] }}</code>@else<span class="status off">No Stripe price</span>@endif</dd>
            @endforeach
        </dl>
    </div>

    <div class="kpis">
        <div class="kpi"><div class="l">Plus subscribers</div><div class="v">{{ number_format($kpis['subscribers']) }}</div><div class="d">{{ $kpis['monthly'] }} monthly · {{ $kpis['yearly'] }} yearly</div></div>
        <div class="kpi"><div class="l">MRR</div><div class="v">{{ $money($kpis['mrr']) }}</div><div class="d">ARR {{ $money($kpis['mrr'] * 12) }}</div></div>
        <div class="kpi"><div class="l">Revenue, 30 days</div><div class="v">{{ $money($kpis['revenue30']) }}</div><div class="d">refunds {{ $money($kpis['refunds30']) }}</div></div>
        <div class="kpi"><div class="l">Revenue, all time</div><div class="v">{{ $money($kpis['revenueAll']) }}</div><div class="d">net of refunds</div></div>
        <div class="kpi"><div class="l">Cancelling</div><div class="v">{{ $kpis['cancelling'] }}</div><div class="d">end at period end</div></div>
        <div class="kpi"><div class="l">Payment problems</div><div class="v">{{ $kpis['pastDue'] }}</div><div class="d">{{ $kpis['churned30'] }} cancelled in 30 days</div></div>
    </div>

    @php($max = max(1, $monthly->max('revenue')))
    <figure class="chart">
        <figcaption class="chart-head"><h3>Revenue per month ({{ strtoupper($currency) }})</h3><span class="chart-total">{{ $money($monthly->sum('revenue') * 100) }} in 12 months</span></figcaption>
        <div class="chart-plot" role="img" aria-label="Revenue per month for the last 12 months. A table follows.">
            <span class="chart-y top">{{ $money($max * 100) }}</span>
            <div class="chart-grid" aria-hidden="true"><i></i><i></i></div>
            <div class="chart-bars" style="--n:{{ $monthly->count() }}">
                @foreach($monthly as $r)
                    <span class="col" tabindex="0" data-tip="{{ $r->date->format('M Y') }}: {{ $money($r->revenue * 100) }}"><span class="bar" style="height:{{ $r->revenue ? max(2, round($r->revenue / $max * 100, 2)) : 0 }}%"></span></span>
                @endforeach
            </div>
        </div>
        <div class="chart-x" aria-hidden="true"><span>{{ $monthly->first()->date->format('M Y') }}</span><span>{{ $monthly->last()->date->format('M Y') }}</span></div>
    </figure>

    <h2>Recent payments</h2>
    @if($payments->isEmpty())
        <p class="muted">No payments yet. They appear here when Stripe sends <code>invoice.paid</code>.</p>
    @else
        <div class="table-scroll"><table>
            <thead><tr><th>Date</th><th>Player</th><th>Plan</th><th class="num">Amount</th><th>Status</th><th class="hide-sm">Invoice</th></tr></thead>
            <tbody>
            @foreach($payments as $p)
                <tr>
                    <td>{{ $p->paid_at->format('j M Y H:i') }}</td>
                    <td>@if($p->user)<a href="{{ route('admin.players.index', ['q' => $p->user->email]) }}">{{ $p->user->name }}</a><br><small class="muted">{{ $p->user->email }}</small>@else<span class="muted">{{ $p->customer_email ?? 'Deleted player' }}</span>@endif</td>
                    <td>{{ ucfirst((string) $p->plan) }} {{ $p->interval }}</td>
                    <td class="num">{{ \App\Services\BillingService::money($p->amount, $p->currency) }}@if($p->amount_refunded)<br><small class="muted">−{{ \App\Services\BillingService::money($p->amount_refunded, $p->currency) }}</small>@endif</td>
                    <td><span class="status {{ $p->status === 'paid' ? 'on' : 'off' }}">{{ str_replace('_', ' ', $p->status) }}</span></td>
                    <td class="hide-sm"><code>{{ $p->stripe_invoice_id }}</code></td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    @endif

    <h2>Subscriptions</h2>
    @if($subscriptions->isEmpty())
        <p class="muted">No active subscriptions.</p>
    @else
        <div class="table-scroll"><table>
            <thead><tr><th>Player</th><th>Plan</th><th>Status</th><th>Renews / ends</th></tr></thead>
            <tbody>
            @foreach($subscriptions as $s)
                <tr>
                    <td>{{ $s->user?->name ?? '—' }}<br><small class="muted">{{ $s->user?->email }}</small></td>
                    <td>{{ ucfirst($s->plan) }} {{ $s->interval }}</td>
                    <td><span class="status {{ $s->status === 'past_due' ? 'off' : 'on' }}">{{ str_replace('_', ' ', $s->status) }}</span></td>
                    <td>{{ $s->current_period_end?->format('j M Y') ?? '—' }}@if($s->cancel_at_period_end) <small class="muted">(cancelling)</small>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    @endif
@endsection
