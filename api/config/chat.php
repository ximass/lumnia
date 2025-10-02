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

];
