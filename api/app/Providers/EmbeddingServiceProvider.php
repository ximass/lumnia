<?php

namespace App\Providers;

use App\Contracts\EmbeddingProvider;
use App\Services\EmbeddingClient;
use App\Services\Providers\LocalEmbeddingProvider;
use App\Services\Providers\RemoteEmbeddingProvider;
use App\Services\Providers\GeminiEmbeddingProvider;
use Illuminate\Support\ServiceProvider;

class EmbeddingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EmbeddingProvider::class, function ($app) {
            $provider = config('providers.default_embedding', 'lm_studio');
            $config = config("providers.embedding.{$provider}");
            
            if (!$config || !($config['enabled'] ?? false)) {
                throw new \InvalidArgumentException("Embedding provider '{$provider}' is not available or enabled.");
            }

            if ($provider === 'openai') {
                return new RemoteEmbeddingProvider(
                    apiUrl: $config['full_endpoint'] ?? rtrim($config['base_url'], '/') . $config['endpoint'],
                    apiKey: $config['api_key'],
                    batchSize: $config['batch_size'],
                    maxRetries: $config['max_retries'],
                    retryDelay: $config['retry_delay'],
                    model: $config['model']
                );
            }

            if ($provider === 'gemini') {
                return new GeminiEmbeddingProvider(
                    batchSize: $config['batch_size'],
                    maxRetries: $config['max_retries'],
                    retryDelay: $config['retry_delay']
                );
            }

            return new LocalEmbeddingProvider(
                provider: $provider,
                batchSize: $config['batch_size'],
                maxRetries: $config['max_retries'],
                retryDelay: $config['retry_delay']
            );
        });

        $this->app->singleton(EmbeddingClient::class, function ($app) {
            return new EmbeddingClient($app->make(EmbeddingProvider::class));
        });
    }

    public function boot(): void
    {
        //
    }
}
