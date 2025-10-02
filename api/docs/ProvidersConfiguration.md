# Providers Configuration Guide

## Overview

The provider configuration system has been reorganized to centralize all LLM and embedding provider settings in a single, well-structured configuration file. This makes it easier to add new providers and manage multiple models.

## Configuration Structure

### Main Configuration File

All provider configurations are now in `config/providers.php`. This file contains:

- **Default provider selection** for LLM and embeddings
- **Provider configurations** for each supported provider
- **Helper functions** to easily retrieve provider configurations

### Supported Providers

#### LLM Providers

1. **LM Studio** (`lm_studio`)
   - OpenAI-compatible API
   - Local inference
   - Configurable model and parameters

2. **Ollama** (`ollama`)
   - Native Ollama API
   - Local inference
   - Configurable model and context format

#### Embedding Providers

1. **LM Studio** (`lm_studio`)
   - OpenAI-compatible embeddings API
   - Local inference
   - Batch processing support

2. **Ollama** (`ollama`)
   - Native Ollama embeddings API
   - Local inference
   - Batch processing support

3. **OpenAI** (`openai`)
   - Remote OpenAI API
   - Requires API key
   - Higher batch size support

## Environment Variables

### Provider Selection

```env
# Choose which LLM provider to use
LLM_PROVIDER=lm_studio

# Choose which embedding provider to use
EMBEDDING_PROVIDER=lm_studio
```

### LM Studio Configuration

```env
LM_STUDIO_ENABLED=true
LM_STUDIO_BASE_URL=http://127.0.0.1:1234
LM_STUDIO_MODEL=gemma-3-1b-it-qat
LM_STUDIO_EMBEDDING_MODEL=text-embedding-nomic-embed-text-v1.5
LM_STUDIO_MAX_TOKENS=30000
LM_STUDIO_TIMEOUT=120
LM_STUDIO_TEMPERATURE=0.7
```

### Ollama Configuration

```env
OLLAMA_ENABLED=true
OLLAMA_BASE_URL=http://localhost:11434
OLLAMA_MODEL=llama2
OLLAMA_EMBEDDING_MODEL=nomic-embed-text
OLLAMA_CONTEXT_FORMAT=conversational
OLLAMA_TIMEOUT=100
OLLAMA_TEMPERATURE=0.7
```

### OpenAI Configuration (Optional)

```env
OPENAI_ENABLED=false
OPENAI_BASE_URL=https://api.openai.com
OPENAI_API_KEY=your-api-key-here
OPENAI_EMBEDDING_MODEL=text-embedding-ada-002
OPENAI_TIMEOUT=60
```

### Embedding General Configuration

```env
EMBEDDING_BATCH_SIZE=10
EMBEDDING_MAX_RETRIES=3
EMBEDDING_RETRY_DELAY=1
```

## Using Configuration in Code

### Getting LLM Configuration

```php
// Get default LLM provider configuration
$provider = config('providers.default_llm'); // Returns: 'lm_studio'
$config = config("providers.llm.{$provider}");

// Access specific settings
$baseUrl = config('providers.llm.lm_studio.base_url');
$model = config('providers.llm.ollama.model');
```

### Getting Embedding Configuration

```php
// Get default embedding provider configuration
$provider = config('providers.default_embedding'); // Returns: 'lm_studio'
$config = config("providers.embedding.{$provider}");

// Access specific settings
$batchSize = config('providers.embedding.lm_studio.batch_size');
$model = config('providers.embedding.ollama.model');
```

### Full Endpoint Construction

The system automatically constructs full endpoints by combining `base_url` and endpoint paths:

```php
// For LLM
$endpoint = rtrim($config['base_url'], '/') . $config['chat_endpoint'];
// Example: http://127.0.0.1:1234/v1/chat/completions

// For Embeddings
$endpoint = rtrim($config['base_url'], '/') . $config['endpoint'];
// Example: http://127.0.0.1:1234/v1/embeddings
```

## Adding New Providers

To add a new provider:

1. **Add provider configuration** to `config/providers.php`:

```php
'llm' => [
    // ... existing providers ...
    
    'new_provider' => [
        'name' => 'New Provider Name',
        'type' => 'custom', // or 'openai_compatible', 'ollama'
        'enabled' => env('NEW_PROVIDER_ENABLED', false),
        'base_url' => env('NEW_PROVIDER_BASE_URL', 'http://localhost:8080'),
        'chat_endpoint' => '/api/chat',
        'model' => env('NEW_PROVIDER_MODEL', 'default-model'),
        'max_tokens' => env('NEW_PROVIDER_MAX_TOKENS', 2000),
        'timeout' => env('NEW_PROVIDER_TIMEOUT', 60),
        'temperature' => env('NEW_PROVIDER_TEMPERATURE', 0.7),
    ],
],
```

2. **Add environment variables** to `.env.example`:

```env
# New Provider Configuration
NEW_PROVIDER_ENABLED=false
NEW_PROVIDER_BASE_URL=http://localhost:8080
NEW_PROVIDER_MODEL=default-model
NEW_PROVIDER_MAX_TOKENS=2000
NEW_PROVIDER_TIMEOUT=60
NEW_PROVIDER_TEMPERATURE=0.7
```

3. **Implement provider-specific logic** if needed in controllers/services.

## Configuration Files Reference

- **`config/providers.php`** - All LLM and embedding provider configurations
- **`config/chat.php`** - Chat-specific settings (context, streaming)
- **`config/services.php`** - Third-party services (not provider-related)
