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
        'semantic_weight' => env('SEARCH_SEMANTIC_WEIGHT', 0.6),
        
        // Beta weight for lexical search (0.0 to 1.0)
        'lexical_weight' => env('SEARCH_LEXICAL_WEIGHT', 0.4),
        
        // Minimum semantic similarity score (0.0 to 1.0)
        'min_semantic_score' => env('SEARCH_MIN_SEMANTIC_SCORE', 0.05),
        
        // Minimum lexical score (0.0 to 1.0)
        'min_lexical_score' => env('SEARCH_MIN_LEXICAL_SCORE', 0.001),
        
        // Minimum combined score threshold for RAG retrieval
        'rag_threshold' => env('SEARCH_RAG_THRESHOLD', 0.15),
        
        // Maximum number of chunks to return before reranking
        'max_chunks' => env('SEARCH_MAX_CHUNKS', 100),
        
        // Number of top chunks to rerank
        'rerank_top_k' => env('SEARCH_RERANK_TOP_K', 10),
        
        // Enable cross-encoder reranking
        'enable_reranking' => env('SEARCH_ENABLE_RERANKING', false),
        
        // Use batch reranking
        'rerank_use_batch' => env('SEARCH_RERANK_USE_BATCH', true),
        
        // Batch size for reranking
        'rerank_batch_size' => env('SEARCH_RERANK_BATCH_SIZE', 5),
    ],
];
