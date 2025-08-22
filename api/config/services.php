<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'embedding' => [
        'provider' => env('EMBEDDING_PROVIDER', 'local'),
        'local_url' => env('EMBEDDING_LOCAL_URL', 'http://127.0.0.1:1234'),
        'remote_url' => env('EMBEDDING_REMOTE_URL', 'https://api.openai.com'),
        'api_key' => env('EMBEDDING_API_KEY'),
        'batch_size' => env('EMBEDDING_BATCH_SIZE', 10),
        'max_retries' => env('EMBEDDING_MAX_RETRIES', 3),
        'retry_delay' => env('EMBEDDING_RETRY_DELAY', 1),
        'model' => env('EMBEDDING_MODEL', 'text-embedding-nomic-embed-text-v1.5'),
    ],

];
