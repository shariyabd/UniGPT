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
        // ~150 tokens keeps each notice/policy split into several passage-level
        // chunks. A larger window (e.g. 512) swallowed each short document whole,
        // diluting topic-specific facts (table rows, single-sentence rules).
        'chunk_size' => env('RAG_CHUNK_SIZE', 150),
        'overlap' => env('RAG_CHUNK_OVERLAP', 40),
    ],

    'retrieval' => [
        'top_k' => env('RAG_TOP_K', 6),
        // Calibrated for text-embedding-3-small, whose relevant-pair cosine
        // similarities sit around 0.3–0.6 (not 0.7+). A 0.7 floor left nothing
        // passing, so the fallback returned a single best match — breaking
        // multi-hop and burying correct second-ranked documents.
        'similarity_threshold' => env('RAG_SIMILARITY_THRESHOLD', 0.35),
        'rerank' => env('RAG_RERANK', true),
    ],

    'citation' => [
        'enabled' => env('RAG_CITATION_ENABLED', true),
        'format' => env('RAG_CITATION_FORMAT', 'inline'), // inline, footnote
        'include_page_numbers' => env('RAG_CITATION_PAGE_NUMBERS', true),
    ],

    'context' => [
        // Must comfortably fit top_k passage chunks (top_k=6 × ~800 chars ≈ 5k).
        // A 3000 floor silently truncated the last retrieved chunks — often the
        // second document of a multi-hop answer — so it is raised to 6000.
        'max_context_length' => env('RAG_MAX_CONTEXT_LENGTH', 6000),
        'window_size' => env('RAG_WINDOW_SIZE', 3), // For surrounding context
    ],

    'cache' => [
        'enabled' => env('RAG_CACHE_ENABLED', true),
        'ttl' => env('RAG_CACHE_TTL', 3600), // seconds
    ],

    'submission_screening' => [
        // Similarity screening of assignment submissions (plagiarism signal
        // on the faculty grading screen). Reuses the embedding provider.
        'enabled' => env('SUBMISSION_SCREENING_ENABLED', true),
        // Flag a pair when any chunk pair reaches this cosine similarity.
        // Near-duplicate text scores >0.9 with text-embedding-3-small;
        // independent answers to the same prompt typically stay below 0.8.
        'flag_threshold' => env('SUBMISSION_SCREENING_THRESHOLD', 0.82),
        // Keep at most this many excerpt pairs per flagged match for review.
        'top_pairs' => env('SUBMISSION_SCREENING_TOP_PAIRS', 3),
        // Cost bound: only the first N chunks of very long submissions are
        // embedded/compared (~150 tokens each).
        'max_chunks' => env('SUBMISSION_SCREENING_MAX_CHUNKS', 40),
    ],
];
