<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Vector Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for vector database providers (Pinecone, FAISS, Chroma)
    |
    */

    'default' => env('VECTOR_DB_DEFAULT', 'pinecone'),

    'providers' => [
        'pinecone' => [
            'api_key' => env('PINECONE_API_KEY'),
            'environment' => env('PINECONE_ENVIRONMENT'),
            'index_name' => env('PINECONE_INDEX_NAME', 'uninexus'),
            'namespace' => env('PINECONE_NAMESPACE', 'default'),
        ],

        'faiss' => [
            'index_path' => env('FAISS_INDEX_PATH', storage_path('embeddings/faiss')),
            'metric' => env('FAISS_METRIC', 'cosine'), // cosine, euclidean, dot
        ],

        'chroma' => [
            'host' => env('CHROMA_HOST', 'localhost'),
            'port' => env('CHROMA_PORT', 8000),
            'collection_name' => env('CHROMA_COLLECTION', 'uninexus'),
        ],
    ],

    'batch_size' => env('VECTOR_BATCH_SIZE', 100),
];
