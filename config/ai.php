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

        /*
        | OpenRouter — used as the automatic fallback tier for chat/completions
        | when OpenAI fails (see the 'fallback' block below). OpenAI-wire
        | compatible. `models` is tried in order and always ends at
        | `openrouter/auto`, which lets OpenRouter route to any available model
        | so a request still resolves when a specific free model is momentarily
        | down. OpenRouter exposes NO embeddings endpoint, so embeddings never
        | route here (they stay pinned to the OpenAI embedding model).
        */
        'openrouter' => [
            'api_key' => env('OPENROUTER_API_KEY'),
            'models' => array_values(array_unique(array_filter([
                env('OPENROUTER_MODEL', 'meta-llama/llama-3.3-70b-instruct:free'),
                env('OPENROUTER_MODEL_SECONDARY', 'google/gemini-2.0-flash-exp:free'),
                'openrouter/auto',
            ]))),
            'max_tokens' => env('OPENROUTER_MAX_TOKENS', 4096),
            'temperature' => env('OPENROUTER_TEMPERATURE', 0.3),
            // OpenRouter attributes free-tier usage via these headers.
            'referer' => env('OPENROUTER_REFERER', env('APP_URL', 'http://localhost')),
            'title' => env('OPENROUTER_TITLE', env('APP_NAME', 'UniNexus')),
        ],

        /*
        | Jina AI — a free, multilingual EMBEDDINGS provider (embed-only; it does
        | not do chat). Used as the embedding fallback when OpenAI is unavailable
        | (OpenRouter has no embeddings endpoint). OpenAI-compatible request shape.
        | `task: text-matching` gives symmetric query/passage vectors, matching how
        | the RAG pipeline embeds queries and documents with the same call.
        */
        'jina' => [
            'api_key' => env('JINA_API_KEY'),
            'model' => env('JINA_EMBEDDING_MODEL', 'jina-embeddings-v3'),
            'dimensions' => env('JINA_EMBEDDING_DIMENSIONS', 1024),
        ],

        'local' => [
            'endpoint' => env('LOCAL_LLM_ENDPOINT', 'http://localhost:8000'),
            'model' => env('LOCAL_LLM_MODEL', 'llama-2'),
            'max_tokens' => env('LOCAL_LLM_MAX_TOKENS', 2048),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider Fallback Chain
    |--------------------------------------------------------------------------
    |
    | When enabled AND an OpenRouter key is configured, chat/completions requests
    | fail over through a chain: the `primary` provider leads and the other real
    | provider becomes the fallback tier, with the offline `mock` always terminal.
    | Set `primary` to 'openai' (OpenAI leads, OpenRouter backs it up) or
    | 'openrouter' (OpenRouter leads, OpenAI backs it up) — configurable from the
    | admin panel. On a failed response (HTTP error, timeout, missing key) the next
    | tier is tried; the terminal `mock` never fails, so a result is always returned.
    | Embeddings are NOT part of this chain — they always prefer OpenAI (OpenRouter
    | has no embeddings endpoint) so the vector space stays consistent.
    |
    */
    'fallback' => [
        'enabled' => env('AI_FALLBACK_ENABLED', false),
        // Which provider leads the chat chain: 'openai' or 'openrouter'.
        'primary' => env('AI_FALLBACK_PRIMARY', 'openai'),
    ],

    'embedding' => [
        // Primary embeddings provider (openai | jina | mock).
        'provider' => env('EMBEDDING_PROVIDER', 'openai'),
        'model' => env('EMBEDDING_MODEL', 'text-embedding-3-small'),
        'dimensions' => env('EMBEDDING_DIMENSIONS', 1536),

        /*
        | Embedding fallback. When enabled, the corpus is DUAL-EMBEDDED — every
        | document is embedded with both the primary and the secondary provider
        | (vectors tagged by model). At query time, if the primary embeddings API
        | fails (e.g. OpenAI quota exhausted at midnight), retrieval transparently
        | falls back to the secondary provider's vectors, so RAG never breaks.
        | Changing embedding settings triggers an automatic corpus re-index.
        */
        'fallback' => [
            'enabled' => env('AI_EMBEDDING_FALLBACK_ENABLED', false),
            'secondary' => env('AI_EMBEDDING_FALLBACK_SECONDARY', 'jina'),
        ],
    ],

    'speech' => [
        'provider' => env('SPEECH_PROVIDER', 'openai'),
        'model' => env('SPEECH_MODEL', 'whisper-1'),
    ],
];
