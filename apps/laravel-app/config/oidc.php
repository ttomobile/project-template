<?php

return [
    'issuer' => env('OIDC_ISSUER', rtrim(env('APP_URL', 'http://localhost:8000'), '/')),
    'default_scopes' => ['openid', 'profile', 'email'],
    'clients' => [
        'fastapi' => [
            'id' => env('FASTAPI_OIDC_CLIENT_ID', 'fastapi-client'),
            'redirect_uri' => env('FASTAPI_OIDC_REDIRECT_URI', 'http://localhost:3000/callback?source=fastapi'),
        ],
        'goa' => [
            'id' => env('GOA_OIDC_CLIENT_ID', 'goa-client'),
            'redirect_uri' => env('GOA_OIDC_REDIRECT_URI', 'http://localhost:3000/callback?source=goa'),
        ],
    ],
    'signing' => [
        'kid' => env('OIDC_KEY_ID', 'demo-key'),
        'private_key' => env('OIDC_PRIVATE_KEY'),
        'private_key_path' => env('OIDC_PRIVATE_KEY_PATH', storage_path('app/oidc/private-key.pem')),
        'public_key' => env('OIDC_PUBLIC_KEY'),
        'public_key_path' => env('OIDC_PUBLIC_KEY_PATH', storage_path('app/oidc/public-key.pem')),
    ],
];
