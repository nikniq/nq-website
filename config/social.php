<?php

return [
    'providers' => [
        'google' => [
            'enabled' => (bool) env('GOOGLE_OAUTH_ENABLED', false),
        ],
    ],
];
