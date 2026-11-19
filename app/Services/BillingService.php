<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Plans, Stripe Checkout/Portal, and syncing subscriptions and payments from Stripe. */
class BillingService
{
    public function __construct(private Stripe $stripe) {}

    public function enabled(): bool
    {
        return $this->stripe->configured() && collect($this->plans())->flatMap(fn ($p) => $p['prices'])->contains(fn ($p) => ! empty($p['stripe_price']));
    }

    /** @return array<string, array{name:string, prices:array<string, array{amount:int, stripe_price:?string}>}> */
    public function plans(): array
    {
        return config('billing.plans', []);
    }

    public function price(string $plan, string $interval): ?array
    {
        return $this->plans()[$plan]['prices'][$interval] ?? null;
    }

    /** Find plan/interval for a Stripe price id. */
    public function planForPrice(?string $priceId): array
    {
        foreach ($this->plans() as $key => $plan) {
            foreach ($plan['prices'] as $interval => $p) {
                if ($priceId && ($p['stripe_price'] ?? null) === $priceId) {
                    return [$key, $interval];
                }
            }
        }

        return [null, null];
    }

    public static function money(int $cents, ?string $currency = null): string
    {
        $currency = strtoupper($currency ?: config('billing.currency', 'usd'));
        $zeroDecimal = in_array($currency, ['JPY', 'KRW', 'VND'], true);
        $value = $zeroDecimal ? $cents : $cents / 100;
        $symbol = ['USD' => '$', 'EUR' => '€', 'GBP' => '£', 'JPY' => '¥', 'AUD' => 'A$', 'CAD' => 'C$', 'SGD' => 'S$'][$currency] ?? $currency.' ';

        return $symbol.number_format($value, $zeroDecimal || fmod($value, 1.0) === 0.0 ? 0 : 2);
    }

    public function checkoutUrl(User $user, string $plan, string $interval): string
    {
        $price = $this->price($plan, $interval);
        $params = [
            'mode' => 'subscription',
            'line_items' => [['price' => $price['stripe_price'], 'quantity' => 1]],
            'client_reference_id' => (string) $user->id,
            'success_url' => route('billing.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('pricing').'?cancelled=1',
            'allow_promotion_codes' => 'true',
            'locale' => in_array(app()->getLocale(), ['ja', 'fr', 'de', 'es'], true) ? app()->getLocale() : 'en',
            'metadata' => ['user_id' => $user->id, 'plan' => $plan, 'interval' => $interval],
            'subscription_data' => ['metadata' => ['user_id' => $user->id, 'plan' => $plan, 'interval' => $interval]],
        ];
        if ($user->stripe_customer_id) {
            $params['customer'] = $user->stripe_customer_id;
        } else {
            $params['customer_email'] = $user->email;
        }

        return (string) $this->stripe->post('checkout/sessions', $params)['url'];
    }

    public function portalUrl(User $user): string
    {
        return (string) $this->stripe->post('billing_portal/sessions', [
            'customer' => $user->stripe_customer_id,
            'return_url' => route('profile').'#plan',
        ])['url'];
    }

    /** After Checkout returns: sync straight away so the player doesn't wait for the webhook. */
    public function syncCheckoutSession(User $user, string $sessionId): bool
    {
        $session = $this->stripe->get('checkout/sessions/'.$sessionId, ['expand' => ['subscription']]);
        if ((string) ($session['client_reference_id'] ?? '') !== (string) $user->id) {
            return false;
        }
        $this->rememberCustomer($user, $session['customer'] ?? null);
        if (is_array($session['subscription'] ?? null)) {
            $this->syncSubscription($session['subscription']);
        }

        return true;
    }

    /** Handle one verified webhook event. Unknown types are ignored. */
    public function handle(array $event): void
    {
        $o = $event['data']['object'] ?? [];
        match ($event['type'] ?? '') {
            'checkout.session.completed' => $this->onCheckout($o),
            'customer.subscription.created', 'customer.subscription.updated', 'customer.subscription.deleted' => $this->syncSubscription($o),
            'invoice.paid' => $this->recordInvoice($o),
            'charge.refunded' => $this->recordRefund($o),
            default => null,
        };
    }

    private function onCheckout(array $s): void
    {
        $user = User::find((int) ($s['client_reference_id'] ?? $s['metadata']['user_id'] ?? 0));
        if ($user) {
            $this->rememberCustomer($user, $s['customer'] ?? null);
        }
    }

    private function rememberCustomer(User $user, ?string $customer): void
    {
        if ($customer && $user->stripe_customer_id !== $customer) {
            $user->forceFill(['stripe_customer_id' => $customer])->saveQuietly();
        }
    }

    private function userFor(array $o): ?User
    {
        $customer = is_array($o['customer'] ?? null) ? ($o['customer']['id'] ?? null) : ($o['customer'] ?? null);
        $user = $customer ? User::withTrashed()->where('stripe_customer_id', $customer)->first() : null;
        if (! $user && ($id = (int) ($o['metadata']['user_id'] ?? 0))) {
            $user = User::withTrashed()->find($id);
            if ($user) {
                $this->rememberCustomer($user, $customer);
            }
        }

        return $user;
    }

    public function syncSubscription(array $s): ?Subscription
    {
        $user = $this->userFor($s);
        if (! $user || empty($s['id'])) {
            Log::warning('billing.subscription.unmatched', ['id' => $s['id'] ?? null, 'customer' => $s['customer'] ?? null]);

            return null;
        }
        $item = $s['items']['data'][0] ?? [];
        $priceId = $item['price']['id'] ?? null;
        [$plan, $interval] = $this->planForPrice($priceId);
        $periodEnd = $s['current_period_end'] ?? $item['current_period_end'] ?? null;

        $sub = Subscription::updateOrCreate(['stripe_subscription_id' => $s['id']], [
            'user_id' => $user->id,
            'stripe_price_id' => $priceId,
            'plan' => $plan ?? ($s['metadata']['plan'] ?? 'plus'),
            'interval' => $interval ?? ($s['metadata']['interval'] ?? ($item['price']['recurring']['interval'] ?? null)),
            'status' => (string) ($s['status'] ?? 'incomplete'),
            'current_period_end' => $periodEnd ? Carbon::createFromTimestamp($periodEnd) : null,
            'cancel_at_period_end' => (bool) ($s['cancel_at_period_end'] ?? false),
            'ended_at' => ! empty($s['ended_at']) ? Carbon::createFromTimestamp($s['ended_at']) : null,
        ]);
        $this->refreshPlus($user);

        return $sub;
    }

    public function refreshPlus(User $user): void
    {
        $until = $user->subscriptions()->whereIn('status', Subscription::LIVE)->max('current_period_end');
        $user->forceFill(['plus_until' => $until])->saveQuietly();
    }

    private function recordInvoice(array $inv): void
    {
        if (empty($inv['id']) || (int) ($inv['amount_paid'] ?? 0) <= 0 || Payment::where('stripe_invoice_id', $inv['id'])->exists()) {
            return; // nothing paid, or already recorded (Stripe retries deliveries)
        }
        $user = $this->userFor($inv + ['metadata' => $inv['subscription_details']['metadata'] ?? []]);
        $line = $inv['lines']['data'][0] ?? [];
        [$plan, $interval] = $this->planForPrice($line['price']['id'] ?? ($line['pricing']['price_details']['price'] ?? null));
        $paidAt = $inv['status_transitions']['paid_at'] ?? $inv['created'] ?? time();

        Payment::create(['stripe_invoice_id' => $inv['id'],
            'user_id' => $user?->id,
            'stripe_payment_intent_id' => is_string($inv['payment_intent'] ?? null) ? $inv['payment_intent'] : null,
            'stripe_subscription_id' => is_string($inv['subscription'] ?? null) ? $inv['subscription'] : null,
            'amount' => (int) $inv['amount_paid'],
            'currency' => strtolower((string) ($inv['currency'] ?? config('billing.currency'))),
            'plan' => $plan ?? 'plus',
            'interval' => $interval,
            'customer_email' => $inv['customer_email'] ?? $user?->email,
            'paid_at' => Carbon::createFromTimestamp($paidAt),
        ]);
    }

    private function recordRefund(array $charge): void
    {
        $pi = $charge['payment_intent'] ?? null;
        $payment = $pi ? Payment::where('stripe_payment_intent_id', $pi)->first() : null;
        if (! $payment) {
            return;
        }
        $refunded = (int) ($charge['amount_refunded'] ?? 0);
        $payment->update([
            'amount_refunded' => min($refunded, $payment->amount),
            'status' => $refunded >= $payment->amount ? 'refunded' : 'partially_refunded',
        ]);
    }

    /** Cancel any live Stripe subscriptions (account deletion). Best effort. */
    public function cancelAll(User $user): void
    {
        if (! $this->stripe->configured()) {
            return;
        }
        foreach ($user->subscriptions()->whereNotIn('status', ['canceled', 'incomplete_expired'])->get() as $sub) {
            try {
                $this->stripe->delete('subscriptions/'.$sub->stripe_subscription_id);
                $sub->update(['status' => 'canceled', 'ended_at' => now()]);
            } catch (Throwable $e) {
                Log::error('billing.cancel.failed', ['subscription' => $sub->stripe_subscription_id, 'error' => $e->getMessage()]);
            }
        }
        $this->refreshPlus($user);
        $this->refreshPlus($user);
    }
}
