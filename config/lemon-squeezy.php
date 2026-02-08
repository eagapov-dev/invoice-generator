<?php

return [
    'api_key' => env('LEMON_SQUEEZY_API_KEY'),
    'store_id' => env('LEMON_SQUEEZY_STORE_ID'),
    'signing_secret' => env('LEMON_SQUEEZY_SIGNING_SECRET'),

    'variants' => [
        'pro' => [
            'monthly' => env('LEMON_SQUEEZY_PRO_MONTHLY_VARIANT_ID'),
            'yearly' => env('LEMON_SQUEEZY_PRO_YEARLY_VARIANT_ID'),
        ],
        'business' => [
            'monthly' => env('LEMON_SQUEEZY_BUSINESS_MONTHLY_VARIANT_ID'),
            'yearly' => env('LEMON_SQUEEZY_BUSINESS_YEARLY_VARIANT_ID'),
        ],
    ],
];
