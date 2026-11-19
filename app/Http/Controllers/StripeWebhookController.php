<?php

namespace App\Http\Controllers;

use App\Services\BillingService;
use App\Services\Stripe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, BillingService $billing): JsonResponse
    {
        $event = Stripe::verifyWebhook($request->getContent(), $request->header('Stripe-Signature'), config('billing.stripe.webhook_secret'));
        if (! $event) {
            return response()->json(['error' => 'invalid signature'], 400);
        }

        try {
            $billing->handle($event);
        } catch (Throwable $e) {
            Log::error('billing.webhook.failed', ['type' => $event['type'] ?? null, 'id' => $event['id'] ?? null, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'failed'], 500); // Stripe retries
        }

        return response()->json(['received' => true]);
    }
}
