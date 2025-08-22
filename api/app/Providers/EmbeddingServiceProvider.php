<?php

namespace App\Providers;

use App\Contracts\EmbeddingProvider;
use App\Services\EmbeddingClient;
use App\Services\Providers\LocalEmbeddingProvider;
use App\Services\Providers\RemoteEmbeddingProvider;
use Illuminate\Support\ServiceProvider;

class EmbeddingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EmbeddingProvider::class, function ($app) {
            $provider = config('services.embedding.provider', 'local');
            
            return match ($provider) {
                'remote' => new RemoteEmbeddingProvider(
                    apiUrl: config('services.embedding.remote_url'),
                    apiKey: config('services.embedding.api_key'),
                    batchSize: config('services.embedding.batch_size', 100),
                    maxRetries: config('services.embedding.max_retries', 3),
                    retryDelay: config('services.embedding.retry_delay', 2),
                    model: config('services.embedding.model', 'text-embedding-ada-002')
                ),
                default => new LocalEmbeddingProvider(
                    apiUrl: config('services.embedding.local_url'),
                    batchSize: config('services.embedding.batch_size', 10),
                    maxRetries: config('services.embedding.max_retries', 3),
                    retryDelay: config('services.embedding.retry_delay', 1),
                    model: config('services.embedding.model', 'all-MiniLM-L6-v2')
                ),
            };
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
