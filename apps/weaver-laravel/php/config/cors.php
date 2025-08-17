<?php

return [
    // Which routes get CORS headers
    'paths' => ['api/*'],

    // Methods allowed (explicit list avoids merge errors)
    'allowed_methods' => ['GET','POST','PUT','PATCH','DELETE','OPTIONS'],

    // Only your domains (must be ARRAY, not string)
    'allowed_origins' => [
        'https://dynamicstatic.net',
        'https://dev.dynamicstatic.net',
    ],

    // Leave patterns empty unless you need regex
    'allowed_origins_patterns' => [],

    // Headers your frontend might send
    'allowed_headers' => ['*'],

    // Headers you expose back
    'exposed_headers' => [],

    // Preflight cache
    'max_age' => 0,

    // Cookies/Authorization across domains
    'supports_credentials' => true,
];