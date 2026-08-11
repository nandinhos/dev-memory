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

    'minimax' => [
        'base_url' => env('MINIMAX_BASE_URL', 'https://api.minimax.io/anthropic'),
        'api_key' => env('MINIMAX_API_KEY'),
        'model' => env('MINIMAX_MODEL', 'MiniMax-M2.5'),
    ],

    'embeddings' => [
        'provider' => env('EMBEDDING_PROVIDER', 'minimax'),
        'dimensions' => (int) env('EMBEDDING_DIMENSIONS', 1536),
        'ollama' => [
            'host' => env('OLLAMA_HOST', 'http://host.docker.internal:11434'),
            'model' => env('OLLAMA_EMBED_MODEL', 'nomic-embed-text'),
        ],
        'minimax' => [
            'base_url' => env('MINIMAX_EMBED_BASE_URL', 'https://api.minimax.io/v1'),
            'model' => env('MINIMAX_EMBED_MODEL', 'embo-01'),
        ],
    ],

    'context7' => [
        'base_url' => env('CONTEXT7_BASE_URL', 'https://context7.com/api/v1'),
        'api_key' => env('CONTEXT7_API_KEY'),
    ],

    'skills_repo' => [
        'path' => env('SKILLS_REPO_PATH', storage_path('app/private/skills-repo')),
    ],

];
