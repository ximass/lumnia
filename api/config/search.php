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
];
