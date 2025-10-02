<?php

namespace App\Services\Providers;

use App\Contracts\EmbeddingProvider;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocalEmbeddingProvider implements EmbeddingProvider
{
    private string $apiUrl;
    private int $batchSize;
    private int $maxRetries;
    private int $retryDelay;
    private string $model;
    private string $provider;

    public function __construct(
        string $provider = null,
        int $batchSize = 10,
        int $maxRetries = 3,
        int $retryDelay = 1,
        string $model = null
    ) {
        $this->provider = $provider ?: config('providers.default_embedding', 'lm_studio');
        $providerConfig = config("providers.embedding.{$this->provider}");
        
        if (!$providerConfig || !($providerConfig['enabled'] ?? false)) {
            throw new \InvalidArgumentException("Embedding provider '{$this->provider}' is not available or enabled.");
        }
        
        $this->apiUrl = rtrim($providerConfig['base_url'], '/') . $providerConfig['endpoint'];
        $this->batchSize = $batchSize;
        $this->maxRetries = $maxRetries;
        $this->retryDelay = $retryDelay;
        $this->model = $model ?: $providerConfig['model'];
    }

    public function getEmbeddings(array $texts): array
    {
        if ($this->provider === 'ollama') {
            return $this->getOllamaEmbeddings($texts);
        }
        
        return $this->getLmStudioEmbeddings($texts);
    }

    private function getLmStudioEmbeddings(array $texts): array
    {
        $response = Http::timeout(60)
            ->post($this->apiUrl, [
                'model' => $this->model,
                'input' => $texts,
            ]);
        
            Log::info('LM Studio Embedding Request', [
                'url' => $this->apiUrl,
                'model' => $this->model,
            ]);

        if (!$response->successful()) {
            throw new RequestException($response);
        }

        $data = $response->json();
        
        if (!isset($data['data']) || !is_array($data['data'])) {
            throw new \InvalidArgumentException('Invalid response format from LM Studio embedding API');
        }

        return array_map(function ($item) {
            if (!isset($item['embedding']) || !is_array($item['embedding'])) {
                throw new \InvalidArgumentException('Invalid embedding format in LM Studio response');
            }
            
            return array_map('floatval', $item['embedding']);
        }, $data['data']);
    }

    private function getOllamaEmbeddings(array $texts): array
    {
        $embeddings = [];
        
        foreach ($texts as $text) {
            $response = Http::timeout(60)
                ->post($this->apiUrl, [
                    'model' => $this->model,
                    'prompt' => $text,
                ]);

            if (!$response->successful()) {
                throw new RequestException($response);
            }

            $data = $response->json();
            
            if (!isset($data['embedding']) || !is_array($data['embedding'])) {
                throw new \InvalidArgumentException('Invalid response format from Ollama embedding API');
            }

            $embeddings[] = array_map('floatval', $data['embedding']);
        }
        
        return $embeddings;
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
