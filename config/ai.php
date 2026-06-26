<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for AI providers (OpenAI, Gemini, Local LLM)
    |
    */

    'default_provider' => env('AI_DEFAULT_PROVIDER', 'openai'),

    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o'),
            'max_tokens' => env('OPENAI_MAX_TOKENS', 4096),
            'temperature' => env('OPENAI_TEMPERATURE', 0.3),
        ],

        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-pro'),
            'max_tokens' => env('GEMINI_MAX_TOKENS', 2048),
            'temperature' => env('GEMINI_TEMPERATURE', 0.7),
        ],

        'local' => [
            'endpoint' => env('LOCAL_LLM_ENDPOINT', 'http://localhost:8000'),
            'model' => env('LOCAL_LLM_MODEL', 'llama-2'),
            'max_tokens' => env('LOCAL_LLM_MAX_TOKENS', 2048),
        ],
    ],

    'embedding' => [
        'provider' => env('EMBEDDING_PROVIDER', 'openai'),
        'model' => env('EMBEDDING_MODEL', 'text-embedding-3-small'),
        'dimensions' => env('EMBEDDING_DIMENSIONS', 1536),
    ],

    'speech' => [
        'provider' => env('SPEECH_PROVIDER', 'openai'),
        'model' => env('SPEECH_MODEL', 'whisper-1'),
    ],
];
