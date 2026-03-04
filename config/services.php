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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
'discord' => [
        'hosting_webhook_url' => env('DISCORD_HOSTING_WEBHOOK_URL'),
    'client_id' => env('DISCORD_CLIENT_ID'),
    'client_secret' => env('DISCORD_CLIENT_SECRET'),
    'redirect' => env('DISCORD_REDIRECT_URI'),
    'webhook_url' => env('DISCORD_WEBHOOK_URL'),
],


    'pterodactyl' => [
        'url' => env('PTERODACTYL_URL', ''),
        'api_key' => env('PTERODACTYL_API_KEY', ''),
        'client_key' => env('PTERODACTYL_CLIENT_KEY', ''),
        'node_id' => env('PTERODACTYL_NODE_ID', 1),
        'eggs' => [
            'et' => env('PTERODACTYL_EGG_ET', 1),
            'etl' => env('PTERODACTYL_EGG_ETL', 2),
            'rtcw' => env('PTERODACTYL_EGG_RTCW', 3),
        ],
        'images' => [
            'et' => env('PTERODACTYL_IMAGE_ET', 'ghcr.io/parkervcp/yolks:debian'),
            'etl' => env('PTERODACTYL_IMAGE_ETL', 'ghcr.io/parkervcp/yolks:debian'),
            'rtcw' => env('PTERODACTYL_IMAGE_RTCW', 'ghcr.io/parkervcp/yolks:debian'),
        ],
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN', ''),
        'chat_id' => env('TELEGRAM_CHAT_ID', ''),
        'enabled' => env('TELEGRAM_ENABLED', true),
        'events' => explode(',', env('TELEGRAM_EVENTS', 'file_uploaded,file_approved,comment_posted,donation,user_registered,contact_form,server_order,news_posted,map_of_week,report')),
    ],

    'omnibot' => [
        'github_repo' => env('GITHUB_REPO', 'wolffileseu/omnibot-waypoints'),
        'github_token' => env('GITHUB_TOKEN', ''),
    ],


    'mistral' => [
        'key' => env('MISTRAL_API_KEY'),
    ],

    'qdrant' => [
        'url' => env('QDRANT_URL', 'http://localhost:6333'),
    ],
];

