<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default LLM Provider
    |--------------------------------------------------------------------------
    |
    | This value determines which LLM provider will be used by default.
    | Supported: "lm_studio", "ollama"
    |
    */

    'default_llm' => env('LLM_PROVIDER', 'lm_studio'),

    /*
    |--------------------------------------------------------------------------
    | Default Embedding Provider
    |--------------------------------------------------------------------------
    |
    | This value determines which provider will be used for embeddings.
    | Supported: "lm_studio", "ollama", "openai"
    |
    */

    'default_embedding' => env('EMBEDDING_PROVIDER', 'lm_studio'),

    /*
    |--------------------------------------------------------------------------
    | LLM Providers Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for each LLM provider. Each provider has specific
    | endpoints, models, and settings.
    |
    */

    'llm' => [
        'lm_studio' => [
            'name' => 'LM Studio',
            'type' => 'openai_compatible',
            'enabled' => env('LM_STUDIO_ENABLED', true),
            'base_url' => env('LM_STUDIO_BASE_URL', 'http://127.0.0.1:1234'),
            'chat_endpoint' => '/v1/chat/completions',
            'model' => env('LM_STUDIO_MODEL', 'gemma-3-1b-it-qat'),
            'max_tokens' => env('LM_STUDIO_MAX_TOKENS', 30000),
            'timeout' => env('LM_STUDIO_TIMEOUT', 120),
            'temperature' => env('LM_STUDIO_TEMPERATURE', 0.7),
        ],

        'ollama' => [
            'name' => 'Ollama',
            'type' => 'ollama',
            'enabled' => env('OLLAMA_ENABLED', true),
            'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
            'chat_endpoint' => '/api/generate',
            'model' => env('OLLAMA_MODEL', 'llama2'),
            'context_format' => env('OLLAMA_CONTEXT_FORMAT', 'conversational'),
            'timeout' => env('OLLAMA_TIMEOUT', 100),
            'temperature' => env('OLLAMA_TEMPERATURE', 0.7),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Embedding Providers Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for each embedding provider. Embeddings are used for
    | semantic search and document similarity.
    |
    */

    'embedding' => [
        'lm_studio' => [
            'name' => 'LM Studio Embeddings',
            'type' => 'openai_compatible',
            'enabled' => env('LM_STUDIO_ENABLED', true),
            'base_url' => env('LM_STUDIO_BASE_URL', 'http://127.0.0.1:1234'),
            'endpoint' => '/v1/embeddings',
            'model' => env('LM_STUDIO_EMBEDDING_MODEL', 'text-embedding-nomic-embed-text-v1.5'),
            'batch_size' => env('EMBEDDING_BATCH_SIZE', 10),
            'max_retries' => env('EMBEDDING_MAX_RETRIES', 3),
            'retry_delay' => env('EMBEDDING_RETRY_DELAY', 1),
            'timeout' => env('LM_STUDIO_TIMEOUT', 120),
        ],

        'ollama' => [
            'name' => 'Ollama Embeddings',
            'type' => 'ollama',
            'enabled' => env('OLLAMA_ENABLED', true),
            'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
            'endpoint' => '/api/embeddings',
            'model' => env('OLLAMA_EMBEDDING_MODEL', 'nomic-embed-text'),
            'batch_size' => env('EMBEDDING_BATCH_SIZE', 10),
            'max_retries' => env('EMBEDDING_MAX_RETRIES', 3),
            'retry_delay' => env('EMBEDDING_RETRY_DELAY', 1),
            'timeout' => env('OLLAMA_TIMEOUT', 100),
        ],

        'openai' => [
            'name' => 'OpenAI Embeddings',
            'type' => 'openai',
            'enabled' => env('OPENAI_ENABLED', false),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com'),
            'endpoint' => '/v1/embeddings',
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-ada-002'),
            'batch_size' => env('EMBEDDING_BATCH_SIZE', 100),
            'max_retries' => env('EMBEDDING_MAX_RETRIES', 3),
            'retry_delay' => env('EMBEDDING_RETRY_DELAY', 2),
            'timeout' => env('OPENAI_TIMEOUT', 60),
        ],
    ],
];
