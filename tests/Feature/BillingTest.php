<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    private function enableStripe(): void
    {
        config([
            'billing.stripe.secret' => 'sk_test_123',
            'billing.stripe.webhook_secret' => 'whsec_test',
            'billing.plans.plus.prices.monthly.stripe_price' => 'price_month',
            'billing.plans.plus.prices.yearly.stripe_price' => 'price_year',
        ]);
    }

    private function webhook(array $event)
    {
        $payload = json_encode($event);
        $t = time();
        $sig = hash_hmac('sha256', $t.'.'.$payload, 'whsec_test');

        return $this->call('POST', '/api/stripe/webhook', [], [], [], ['HTTP_STRIPE_SIGNATURE' => "t=$t,v1=$sig", 'CONTENT_TYPE' => 'application/json'], $payload);
    }

    private function subscriptionEvent(string $type, User $user, string $status = 'active', ?int $end = null): array
    {
        return ['id' => 'evt_'.uniqid(), 'type' => $type, 'data' => ['object' => [
            'id' => 'sub_1', 'object' => 'subscription', 'customer' => 'cus_1', 'status' => $status, 'cancel_at_period_end' => false,
            'current_period_end' => $end ?? now()->addMonth()->timestamp, 'metadata' => ['user_id' => (string) $user->id],
            'items' => ['data' => [['price' => ['id' => 'price_month', 'recurring' => ['interval' => 'month']]]]],
        ]]];
    }

    public function test_pricing_page_without_stripe_shows_coming_soon(): void
    {
        $this->get('/pricing')->assertOk()->assertSee('Simple pricing')->assertSee('Coming soon')->assertSee('$3')->assertSee('$30')->assertSee('Save 17%');
        $this->get('/sitemap.xml')->assertSee(route('pricing'));
    }

    public function test_checkout_redirects_to_stripe_with_the_right_price(): void
    {
        $this->enableStripe();
        Http::fake(['api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_1', 'url' => 'https://checkout.stripe.com/c/pay/cs_1'])]);
        $user = User::factory()->create();

        $this->get('/pricing')->assertSee('Log in to get Plus');
        $this->actingAs($user)->get('/pricing')->assertSee('Get Plus');
        $this->actingAs($user)->post('/billing/checkout', ['plan' => 'plus', 'interval' => 'yearly'])->assertRedirect('https://checkout.stripe.com/c/pay/cs_1');

        Http::assertSent(fn (HttpRequest $r) => $r['line_items'][0]['price'] === 'price_year' && $r['client_reference_id'] === (string) $user->id
            && $r['customer_email'] === $user->email && $r['mode'] === 'subscription' && $r->hasHeader('Authorization', 'Bearer sk_test_123'));
    }

    public function test_webhooks_turn_plus_on_record_sales_and_turn_it_off(): void
    {
        $this->enableStripe();
        $user = User::factory()->create(['is_public' => true]);

        $this->call('POST', '/api/stripe/webhook', [], [], [], ['HTTP_STRIPE_SIGNATURE' => 't=1,v1=bad'], '{}')->assertStatus(400);

        $this->webhook($this->subscriptionEvent('customer.subscription.created', $user))->assertOk();
        $user->refresh();
        $this->assertTrue($user->isPlus());
        $this->assertSame('cus_1', $user->stripe_customer_id);
        $this->get('/players/'.$user->id)->assertSee('plus-badge', false);
        $this->actingAs($user)->get('/profile')->assertSee('Renews on')->assertSee('Manage billing');

        $this->webhook(['id' => 'evt_i', 'type' => 'invoice.paid', 'data' => ['object' => [
            'id' => 'in_1', 'customer' => 'cus_1', 'amount_paid' => 300, 'currency' => 'usd', 'payment_intent' => 'pi_1', 'subscription' => 'sub_1',
            'status_transitions' => ['paid_at' => now()->timestamp], 'lines' => ['data' => [['price' => ['id' => 'price_month']]]],
        ]]])->assertOk();
        $this->webhook(['id' => 'evt_i2', 'type' => 'invoice.paid', 'data' => ['object' => ['id' => 'in_1', 'customer' => 'cus_1', 'amount_paid' => 300, 'currency' => 'usd']]])->assertOk();
        $this->assertSame(1, Payment::count(), 'invoice.paid is idempotent');
        $this->assertSame($user->id, Payment::first()->user_id);
        $this->assertSame('monthly', Payment::first()->interval);

        $this->webhook(['id' => 'evt_r', 'type' => 'charge.refunded', 'data' => ['object' => ['payment_intent' => 'pi_1', 'amount_refunded' => 300]]])->assertOk();
        $this->assertSame('refunded', Payment::first()->status);

        $this->webhook($this->subscriptionEvent('customer.subscription.deleted', $user, 'canceled'))->assertOk();
        $this->assertFalse($user->fresh()->isPlus());
    }

    public function test_success_page_syncs_the_checkout_session(): void
    {
        $this->enableStripe();
        $user = User::factory()->create();
        Http::fake(['api.stripe.com/v1/checkout/sessions/*' => Http::response([
            'id' => 'cs_1', 'client_reference_id' => (string) $user->id, 'customer' => 'cus_9',
            'subscription' => $this->subscriptionEvent('x', $user)['data']['object'] + [],
        ])]);

        $this->actingAs($user)->get('/billing/success?session_id=cs_1')->assertOk()->assertSee('Plus is active');
        $this->assertTrue($user->fresh()->isPlus());
    }

    public function test_admin_sales_page_and_export(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Payment::create(['user_id' => $admin->id, 'stripe_invoice_id' => 'in_x', 'amount' => 3000, 'currency' => 'usd', 'plan' => 'plus', 'interval' => 'yearly', 'paid_at' => now()]);

        $this->actingAs($admin)->get('/admin/sales')->assertOk()->assertSee('Stripe setup')->assertSee('$30')->assertSee('in_x');
        $csv = $this->actingAs($admin)->get('/admin/sales/export')->assertOk()->streamedContent();
        $this->assertStringContainsString('in_x', $csv);
        $this->actingAs(User::factory()->create())->get('/admin/sales')->assertForbidden();
    }

    public function test_deleting_an_account_cancels_its_subscription_but_keeps_the_sale(): void
    {
        $this->enableStripe();
        $user = User::factory()->create(['password' => 'secret-pass']);
        $this->webhook($this->subscriptionEvent('customer.subscription.created', $user))->assertOk();
        Payment::create(['user_id' => $user->id, 'stripe_invoice_id' => 'in_k', 'amount' => 300, 'currency' => 'usd', 'paid_at' => now()]);
        Http::fake(['api.stripe.com/v1/subscriptions/sub_1' => Http::response(['id' => 'sub_1', 'status' => 'canceled'])]);

        $this->actingAs($user)->delete('/profile', ['confirm_email' => $user->email])->assertRedirect();

        Http::assertSent(fn (HttpRequest $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/subscriptions/sub_1'));
        $this->assertNull(User::withTrashed()->find($user->id));
        $this->assertNull(Payment::where('stripe_invoice_id', 'in_k')->first()->user_id);
    }
}
