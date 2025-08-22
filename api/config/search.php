<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Search Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for hybrid search combining semantic and lexical search.
    |
    */

    // Language for full-text search (PostgreSQL text search configuration)
    'language' => env('SEARCH_LANGUAGE', 'portuguese'),

    'scoring' => [
        // Alpha weight for semantic search (0.0 to 1.0)
        'semantic_weight' => env('SEARCH_SEMANTIC_WEIGHT', 0.7),
        
        // Beta weight for lexical search (0.0 to 1.0)
        'lexical_weight' => env('SEARCH_LEXICAL_WEIGHT', 0.3),
        
        // Maximum number of chunks to return before reranking
        'max_chunks' => env('SEARCH_MAX_CHUNKS', 100),
        
        // Number of top chunks to rerank
        'rerank_top_k' => env('SEARCH_RERANK_TOP_K', 10),
        
        // Enable cross-encoder reranking
        'enable_reranking' => env('SEARCH_ENABLE_RERANKING', false),
    ],

    'reranker' => [
        'provider' => env('RERANKER_PROVIDER', 'local'),
        'local_url' => env('RERANKER_LOCAL_URL', 'http://127.0.0.1:1234'),
        'remote_url' => env('RERANKER_REMOTE_URL', 'https://api.openai.com'),
        'api_key' => env('RERANKER_API_KEY'),
        'model' => env('RERANKER_MODEL', 'ms-marco-MiniLM-L-6-v2'),
        'max_retries' => env('RERANKER_MAX_RETRIES', 3),
        'retry_delay' => env('RERANKER_RETRY_DELAY', 1),
    ],
];
