# Google Gemini Integration

This document describes how to configure and use Google Gemini as an LLM and embedding provider.

## Configuration

### 1. Get API Key

1. Go to [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Create a new API key
3. Copy the key to your `.env` file

### 2. Environment Variables

Add the following variables to your `.env` file:

```env
# Google Gemini Configuration
GEMINI_ENABLED=true
GEMINI_API_KEY=your-api-key-here
GEMINI_BASE_URL=https://generativelanguage.googleapis.com
GEMINI_MODEL=gemini-pro
GEMINI_EMBEDDING_MODEL=models/embedding-001
GEMINI_MAX_TOKENS=30000
GEMINI_TIMEOUT=120
GEMINI_TEMPERATURE=0.7
```

### 3. Set Default Providers

To use Gemini as default LLM provider:

```env
LLM_PROVIDER=gemini
```

To use Gemini as default embedding provider:

```env
EMBEDDING_PROVIDER=gemini
```

## Available Models

### LLM Models

- `gemini-pro` - Best for text generation (default)
- `gemini-pro-vision` - For multimodal tasks (text + images)

### Embedding Models

- `models/embedding-001` - Text embedding model (default)

## Features

### LLM Features

- ✅ Text generation
- ✅ Streaming responses
- ✅ Conversation history support
- ✅ Persona integration
- ✅ Temperature control
- ✅ Max tokens configuration

### Embedding Features

- ✅ Text embeddings generation
- ✅ Batch processing
- ✅ Automatic retry with exponential backoff
- ✅ RAG integration

## Usage Examples

### Using Gemini for Chat

Once configured, the system will automatically use Gemini when `LLM_PROVIDER=gemini` is set.

```php
// This happens automatically in ChatController
$llmController = new LLMController($ragService);
$result = $llmController->generateAnswer($message->text, $chat, true, $callback);
```

### Using Gemini for Embeddings

When `EMBEDDING_PROVIDER=gemini` is set, all embedding operations will use Gemini:

```php
// This happens automatically in RAGService and SearchController
$queryEmbeddings = $this->embeddingClient->getEmbeddings([$query]);
```

## API Endpoints

### Chat Endpoint

```
POST https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent
```

### Streaming Endpoint

```
POST https://generativelanguage.googleapis.com/v1beta/models/{model}:streamGenerateContent?alt=sse
```

### Embedding Endpoint

```
POST https://generativelanguage.googleapis.com/v1beta/{model}:embedContent
```

## Rate Limits

Google Gemini API has the following rate limits (as of 2024):

- **Free tier**: 60 requests per minute
- **Pay-as-you-go**: Custom limits

Check [Google AI Studio](https://ai.google.dev/pricing) for current pricing and limits.

## Error Handling

The integration includes automatic error handling and retry logic:

- Connection errors are logged and retried
- Invalid responses are caught and logged
- API key errors are clearly reported

## Troubleshooting

### API Key Issues

If you see "Google Gemini API key is not configured":

1. Check that `GEMINI_API_KEY` is set in `.env`
2. Verify the API key is valid
3. Ensure `GEMINI_ENABLED=true`

### Rate Limit Errors

If you hit rate limits:

1. Reduce `EMBEDDING_BATCH_SIZE` in `.env`
2. Increase `EMBEDDING_RETRY_DELAY`
3. Consider upgrading your API plan

### Timeout Errors

If requests timeout:

1. Increase `GEMINI_TIMEOUT` value
2. Reduce `GEMINI_MAX_TOKENS`
3. Check your internet connection

## Comparison with Other Providers

| Feature | Gemini | LM Studio | Ollama |
|---------|--------|-----------|--------|
| Cloud-based | ✅ | ❌ | ❌ |
| Free tier | ✅ | ✅ | ✅ |
| Streaming | ✅ | ✅ | ✅ |
| Embeddings | ✅ | ✅ | ✅ |
| Multimodal | ✅ | ❌ | ❌ |
| Local deployment | ❌ | ✅ | ✅ |

## References

- [Google AI Studio](https://makersuite.google.com/)
- [Gemini API Documentation](https://ai.google.dev/docs)
- [Gemini API Pricing](https://ai.google.dev/pricing)
