<?php

namespace App\Http\Controllers;

use App\Services\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class BillingController extends Controller
{
    public function __construct(private BillingService $billing) {}

    public function pricing(Request $request): View
    {
        return view('pricing', [
            'enabled' => $this->billing->enabled(),
            'plans' => $this->billing->plans(),
            'user' => $request->user(),
            'subscription' => $request->user()?->currentSubscription(),
        ]);
    }

    public function checkout(Request $request): RedirectResponse
    {
        $data = $request->validate(['plan' => ['required', 'string'], 'interval' => ['required', 'in:monthly,yearly']]);
        $price = $this->billing->price($data['plan'], $data['interval']);
        if (! $this->billing->enabled() || ! $price || empty($price['stripe_price'])) {
            return redirect()->route('pricing')->withErrors(['billing' => __('Payments are not available right now. Please try again later.')]);
        }
        $user = $request->user();
        if ($user->isPlus()) {
            return redirect()->route('profile', ['#plan'])->with('status', __('You already have Plus.'));
        }

        try {
            return redirect()->away($this->billing->checkoutUrl($user, $data['plan'], $data['interval']));
        } catch (Throwable $e) {
            Log::error('billing.checkout.failed', ['user' => $user->id, 'error' => $e->getMessage()]);

            return redirect()->route('pricing')->withErrors(['billing' => __('We could not reach the payment page. Please try again.')]);
        }
    }

    public function success(Request $request): View
    {
        $user = $request->user();
        if ($id = $request->query('session_id')) {
            try {
                $this->billing->syncCheckoutSession($user, (string) $id);
            } catch (Throwable $e) {
                Log::warning('billing.success.sync_failed', ['user' => $user->id, 'error' => $e->getMessage()]);
            }
        }

        return view('billing.success', ['user' => $user->fresh()]);
    }

    public function portal(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->stripe_customer_id || ! $this->billing->enabled()) {
            return redirect()->route('pricing');
        }

        try {
            return redirect()->away($this->billing->portalUrl($user));
        } catch (Throwable $e) {
            Log::error('billing.portal.failed', ['user' => $user->id, 'error' => $e->getMessage()]);

            return redirect()->route('profile')->withErrors(['billing' => __('We could not open billing. Please try again.')]);
        }
    }
}
