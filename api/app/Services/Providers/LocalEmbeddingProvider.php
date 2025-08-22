<?php

namespace App\Services\Providers;

use App\Contracts\EmbeddingProvider;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class LocalEmbeddingProvider implements EmbeddingProvider
{
    private string $apiUrl;
    private int $batchSize;
    private int $maxRetries;
    private int $retryDelay;
    private string $model;

    public function __construct(
        string $apiUrl = null,
        int $batchSize = 10,
        int $maxRetries = 3,
        int $retryDelay = 1,
        string $model = 'text-embedding-nomic-embed-text-v1.5'
    ) {
        $this->apiUrl = $apiUrl ?: config('services.embedding.local_url', 'http://127.0.0.1:1234');
        $this->batchSize = $batchSize;
        $this->maxRetries = $maxRetries;
        $this->retryDelay = $retryDelay;
        $this->model = $model;
    }

    public function getEmbeddings(array $texts): array
    {
        $response = Http::timeout(60)
            ->post("{$this->apiUrl}/v1/embeddings", [
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
