<?php

return [
    'providers' => [
        'google' => [
            'driver' => 'google',
            'scopes' => ['openid', 'profile', 'email'],
            'callback_path' => env('GOOGLE_REDIRECT_URI', env('APP_URL') . '/auth/google/callback'),
            'token_url' => 'https://oauth2.googleapis.com/token',
        ],
        'microsoft' => [
            'driver' => 'microsoft',
            'scopes' => ['openid', 'profile', 'email'],
            'callback_path' => env('MICROSOFT_REDIRECT_URI', env('APP_URL') . '/auth/microsoft/callback'),
            'tenant'    => env('MICROSOFT_TENANT', 'common'),
            'token_url' => fn () => 'https://login.microsoftonline.com/'
                            . env('MICROSOFT_TENANT', 'common')
                            . '/oauth2/v2.0/token',
        ],
    ],
];
