<?php

namespace App\Services\Providers;

use App\Contracts\EmbeddingProvider;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class RemoteEmbeddingProvider implements EmbeddingProvider
{
    private string $apiUrl;
    private string $apiKey;
    private int $batchSize;
    private int $maxRetries;
    private int $retryDelay;
    private string $model;

    public function __construct(
        string $apiUrl = null,
        string $apiKey = null,
        int $batchSize = 100,
        int $maxRetries = 3,
        int $retryDelay = 2,
        string $model = 'text-embedding-ada-002'
    ) {
        $provider = config('providers.default_embedding', 'openai');
        $providerConfig = config("providers.embedding.{$provider}");
        
        $this->apiUrl = $apiUrl ?: ($providerConfig['full_endpoint'] ?? rtrim($providerConfig['base_url'], '/') . $providerConfig['endpoint']);
        $this->apiKey = $apiKey ?: ($providerConfig['api_key'] ?? '');
        $this->batchSize = $batchSize;
        $this->maxRetries = $maxRetries;
        $this->retryDelay = $retryDelay;
        $this->model = $model;
    }

    public function getEmbeddings(array $texts): array
    {
        $response = Http::timeout(120)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post($this->apiUrl, [
                'model' => $this->model,
                'input' => $texts,
            ]);

        if (!$response->successful()) {
            throw new RequestException($response);
        }

        $data = $response->json();
        
        if (!isset($data['data']) || !is_array($data['data'])) {
            throw new \InvalidArgumentException('Invalid response format from embedding API');
        }

        return array_map(function ($item) {
            if (!isset($item['embedding']) || !is_array($item['embedding'])) {
                throw new \InvalidArgumentException('Invalid embedding format in response');
            }
            
            return array_map('floatval', $item['embedding']);
        }, $data['data']);
    }

    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    public function getRetryDelay(): int
    {
        return $this->retryDelay;
    }
}
