<?php

return [
    // Shown in the header, page titles and the SSO consent copy.
    'name' => env('PORTAL_NAME', 'NikniQ Games'),
    'tagline' => env('PORTAL_TAGLINE', 'Play, collect favourites, climb the leaderboards.'),

    // Google Analytics 4 measurement id. The tag renders only in production
    // (or when GA_ALWAYS=true), so local and test traffic never reaches it.
    'ga_measurement_id' => env('GA_MEASUREMENT_ID', 'G-34RWEG01MV'),
    'ga_always' => (bool) env('GA_ALWAYS', false),

    // How long the sign-up confirmation link stays valid.
    'verify_ttl_minutes' => (int) env('VERIFY_TTL_MINUTES', 60),

    // How long an authorization code stays valid after /sso/authorize.
    'code_ttl_minutes' => (int) env('SSO_CODE_TTL_MINUTES', 5),

    // How long a player token issued to a game stays valid.
    'token_ttl_days' => (int) env('SSO_TOKEN_TTL_DAYS', 30),

    // Event names that count as one completed play in the aggregates.
    'play_events' => ['game_over', 'finish', 'complete', 'round_end', 'session_end'],

    // Default and maximum rows returned by leaderboards.
    'leaderboard_limit' => 25,
    'leaderboard_max' => 100,

    // Maximum events accepted in one batched POST.
    'max_batch' => 50,

    // How many recent activity rows the dashboard shows.
    'activity_limit' => 30,
];
