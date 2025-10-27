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

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'video_download_api' => [
        'key' => env('VIDEO_DOWNLOAD_API_KEY'),
        'base_url' => env('VIDEO_DOWNLOAD_API_BASE_URL', 'https://p.savenow.to/ajax/download.php'),
        'timeout' => (int) env('VIDEO_DOWNLOAD_API_TIMEOUT', 120),
        'retry_times' => (int) env('VIDEO_DOWNLOAD_API_RETRY', 3),
        'rate_limit' => (int) env('VIDEO_DOWNLOAD_API_RATE_LIMIT', 100),
        'cache_ttl' => (int) env('CACHE_VIDEO_INFO_TTL', 3600),
        'max_audio_duration' => (int) env('MAX_AUDIO_DURATION', 7200),
        'max_video_size' => (int) env('MAX_VIDEO_SIZE', 2048),
    ],

];
