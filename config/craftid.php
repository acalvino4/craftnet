<?php

use craft\helpers\App;

return [
    '*' => [
        'craftIdUrl' => App::env('URL_ID'),
        'consoleUrl' => App::env('URL_CONSOLE'),
        'stripePublicKey' => App::env('STRIPE_PUBLIC_KEY'),
        'stripeApiKey' => App::env('STRIPE_API_KEY'),
        'stripeClientId' => App::env('STRIPE_CLIENT_ID'),
        'oauthServer' => [
            'accessTokenExpiry' => 'PT1H',
            'refreshTokenExpiry' => 'P1M',
            'authCodeExpiry' => 'P1M',
            'clientApprovalTemplate' => 'oauth/authorization',
            'enabledGrants' => [
                'ClientCredentialsGrant',
                'PasswordGrant',
                'RefreshTokenGrant',
                'ImplicitGrant',
                'AuthCodeGrant',
            ],
            'grants' => [
                'ClientCredentialsGrant' => 'Client Credentials Grant',
                'PasswordGrant' => 'Password Grant',
                'AuthCodeGrant' => 'Authorization Code Grant',
                'ImplicitGrant' => 'Implicit Grant',
                'RefreshTokenGrant' => 'Refresh Token Grant',
            ],
            'privateKey' => __DIR__ . '/keys/oauth-server',
            'publicKey' => __DIR__ . '/keys/oauth-server.pub',
            'encryptionKey' => App::env('OAUTH_ENC_KEY'),
            'scopes' => [
                'purchasePlugins' => "Purchase plugins",
                'existingPlugins' => "List existing plugins",
                'transferPluginLicense' => "Transfer plugin license",
                'deassociatePluginLicense' => "Deassociate plugin license",
            ]
        ],
        'enablePluginStoreCache' => false,
    ],
    'prod' => [
        'enablePluginStoreCache' => true,
    ],
    'stage' => [
        'enablePluginStoreCache' => true,
    ],
];
