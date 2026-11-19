<?php

/*
 * Pricing plans and Stripe. Prices live in Stripe; set each plan's Price ID
 * (price_...) in .env. Amounts here are only for display and must match Stripe.
 * Billing is switched on when STRIPE_SECRET and at least one price are set.
 */
return [
    'stripe' => [
        'secret' => env('STRIPE_SECRET'),            // sk_live_... / sk_test_...
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'), // whsec_...
        'api' => env('STRIPE_API_BASE', 'https://api.stripe.com/v1'),
    ],

    'currency' => env('BILLING_CURRENCY', 'usd'),

    // Plan key => display data and Stripe prices per interval (amount in cents).
    'plans' => [
        'plus' => [
            'name' => 'Plus',
            'prices' => [
                'monthly' => ['amount' => (int) env('PLUS_MONTHLY_CENTS', 300), 'stripe_price' => env('STRIPE_PRICE_PLUS_MONTHLY')],
                'yearly' => ['amount' => (int) env('PLUS_YEARLY_CENTS', 3000), 'stripe_price' => env('STRIPE_PRICE_PLUS_YEARLY')],
            ],
        ],
    ],
];
