<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Sales: subscriptions and the payments ledger synced from Stripe. Read-only. */
class SalesController extends Controller
{
    public function index(BillingService $billing): View
    {
        $live = Subscription::with('user:id,name,email')->whereIn('status', Subscription::LIVE)->get();
        $mrr = $live->sum(function (Subscription $s) use ($billing) {
            $amount = $billing->price($s->plan, (string) $s->interval)['amount'] ?? 0;

            return $s->interval === 'yearly' ? (int) round($amount / 12) : $amount;
        });

        $since30 = now()->subDays(30);
        $net = fn ($q) => (int) $q->sum('amount') - (int) $q->sum('amount_refunded');

        $months = collect(range(11, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $byMonth = Payment::where('paid_at', '>=', $months->first())->get(['paid_at', 'amount', 'amount_refunded'])
            ->groupBy(fn ($p) => $p->paid_at->format('Y-m'));
        $monthly = $months->map(fn (Carbon $m) => (object) [
            'date' => $m,
            'revenue' => (int) round((($byMonth[$m->format('Y-m')] ?? collect())->sum(fn ($p) => $p->amount - $p->amount_refunded)) / 100),
        ]);

        return view('admin.sales.index', [
            'enabled' => $billing->enabled(),
            'stripe' => [
                'secret' => (string) config('billing.stripe.secret'),
                'webhook' => (bool) config('billing.stripe.webhook_secret'),
                'prices' => collect($billing->plans())->flatMap(fn ($p, $k) => collect($p['prices'])->mapWithKeys(fn ($v, $i) => ["$k $i" => $v]))->all(),
            ],
            'kpis' => [
                'subscribers' => $live->count(),
                'monthly' => $live->where('interval', 'monthly')->count(),
                'yearly' => $live->where('interval', 'yearly')->count(),
                'mrr' => $mrr,
                'revenue30' => $net(Payment::where('paid_at', '>=', $since30)),
                'revenueAll' => $net(Payment::query()),
                'refunds30' => (int) Payment::where('paid_at', '>=', $since30)->sum('amount_refunded'),
                'cancelling' => $live->where('cancel_at_period_end', true)->count(),
                'pastDue' => Subscription::where('status', 'past_due')->count(),
                'churned30' => Subscription::where('status', 'canceled')->where('updated_at', '>=', $since30)->count(),
            ],
            'monthly' => $monthly,
            'payments' => Payment::with('user:id,name,email')->latest('paid_at')->limit(50)->get(),
            'subscriptions' => Subscription::with('user:id,name,email')->whereIn('status', ['active', 'trialing', 'past_due'])->orderBy('current_period_end')->get(),
            'currency' => config('billing.currency'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['paid_at', 'invoice', 'player_id', 'email', 'plan', 'interval', 'currency', 'amount', 'refunded', 'net', 'status']);
            Payment::orderBy('paid_at')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $p) {
                    fputcsv($out, [$p->paid_at->toDateTimeString(), $p->stripe_invoice_id, $p->user_id, $p->customer_email, $p->plan, $p->interval, strtoupper($p->currency),
                        number_format($p->amount / 100, 2, '.', ''), number_format($p->amount_refunded / 100, 2, '.', ''), number_format($p->net() / 100, 2, '.', ''), $p->status]);
                }
            });
            fclose($out);
        }, 'nikniq-sales-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }
}
