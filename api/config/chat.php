<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Chat Context Configuration
    |--------------------------------------------------------------------------
    |
    | These options control how the chat system manages conversation context
    | and memory across different messages in a chat session.
    |
    */

    'context' => [
        /*
        |--------------------------------------------------------------------------
        | Context Limit
        |--------------------------------------------------------------------------
        |
        | This value determines how many previous messages should be included
        | as context when generating responses. A higher value provides more
        | context but may slow down responses and consume more tokens.
        |
        */
        'limit' => env('CHAT_CONTEXT_LIMIT', 10),

        /*
        |--------------------------------------------------------------------------
        | Enable Context
        |--------------------------------------------------------------------------
        |
        | This value determines whether conversation context should be included
        | when generating responses. Set to false to disable context entirely.
        |
        */
        'enabled' => env('CHAT_CONTEXT_ENABLED', true),

        /*
        |--------------------------------------------------------------------------
        | Context Window Size
        |--------------------------------------------------------------------------
        |
        | Maximum number of tokens that can be used for context. This helps
        | prevent exceeding the model's token limits.
        |
        */
        'max_tokens' => env('CHAT_CONTEXT_MAX_TOKENS', 4000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Streaming Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for chat streaming functionality. When enabled, responses
    | will be delivered in real-time chunks instead of waiting for the complete
    | response.
    |
    */

    'streaming' => [
        /*
        |--------------------------------------------------------------------------
        | Enable Streaming
        |--------------------------------------------------------------------------
        |
        | This value determines whether streaming responses should be used
        | by default for all chat interactions. Set to false to disable
        | streaming and use traditional request-response pattern.
        |
        */
        'enabled' => env('CHAT_STREAMING_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | LLM Provider Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for different LLM providers and their specific settings
    | for handling conversation context.
    |
    */

    'llm' => [
        /*
        |--------------------------------------------------------------------------
        | Default Provider
        |--------------------------------------------------------------------------
        |
        | The default LLM provider to use. Options: 'llm_studio', 'ollama'
        |
        */
        'default_provider' => env('LLM_DEFAULT_PROVIDER', 'llm_studio'),
    ],

    'providers' => [
        'llm_studio' => [
            'endpoint' => env('LLM_API_URL') . '/v1/chat/completions',
            'max_tokens' => env('LLM_STUDIO_MAX_TOKENS', 1000),
            'model' => env('LLM_STUDIO_MODEL', 'gemma-3-1b-it-qat'),
            'timeout' => env('LLM_STUDIO_TIMEOUT', 120),
            'type' => 'openai_compatible',
        ],
        
        'ollama' => [
            'endpoint' => env('LLM_API_URL') . '/api/generate',
            'model' => env('LLM_DEFAULT_MODEL', 'llama2'),
            'context_format' => env('OLLAMA_CONTEXT_FORMAT', 'conversational'),
            'timeout' => env('OLLAMA_TIMEOUT', 100),
            'type' => 'ollama',
        ],
    ],

];
