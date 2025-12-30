<?php

return [
    /*
    |--------------------------------------------------------------------------
    | RAG (Retrieval-Augmented Generation) Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for RAG system including chunking, retrieval, and citation
    |
    */

    'chunking' => [
        'strategy' => env('RAG_CHUNKING_STRATEGY', 'semantic'), // semantic, fixed, recursive
        'chunk_size' => env('RAG_CHUNK_SIZE', 512),
        'overlap' => env('RAG_CHUNK_OVERLAP', 50),
    ],

    'retrieval' => [
        'top_k' => env('RAG_TOP_K', 5),
        'similarity_threshold' => env('RAG_SIMILARITY_THRESHOLD', 0.7),
        'rerank' => env('RAG_RERANK', true),
    ],

    'citation' => [
        'enabled' => env('RAG_CITATION_ENABLED', true),
        'format' => env('RAG_CITATION_FORMAT', 'inline'), // inline, footnote
        'include_page_numbers' => env('RAG_CITATION_PAGE_NUMBERS', true),
    ],

    'context' => [
        'max_context_length' => env('RAG_MAX_CONTEXT_LENGTH', 3000),
        'window_size' => env('RAG_WINDOW_SIZE', 3), // For surrounding context
    ],

    'cache' => [
        'enabled' => env('RAG_CACHE_ENABLED', true),
        'ttl' => env('RAG_CACHE_TTL', 3600), // seconds
    ],
];
