<?php

namespace App\Services\Providers;

use App\Contracts\EmbeddingProvider;
use App\Services\GeminiClient;
use Illuminate\Support\Facades\Log;

class GeminiEmbeddingProvider implements EmbeddingProvider
{
    private int $batchSize;
    private int $maxRetries;
    private int $retryDelay;
    private GeminiClient $geminiClient;

    public function __construct(
        int $batchSize = 10,
        int $maxRetries = 3,
        int $retryDelay = 1
    ) {
        $providerConfig = config('providers.embedding.gemini');
        
        $this->batchSize = $batchSize;
        $this->maxRetries = $maxRetries;
        $this->retryDelay = $retryDelay;
        $this->geminiClient = new GeminiClient();
    }

    public function getEmbeddings(array $texts): array
    {
        $embeddings = [];
        
        foreach ($texts as $text) {
            try {
                $embedding = $this->geminiClient->generateEmbedding($text);
                $embeddings[] = $embedding;
            } catch (\Exception $e) {
                Log::error('Gemini embedding generation failed', [
                    'error' => $e->getMessage(),
                    'text_length' => strlen($text)
                ]);
                throw $e;
            }
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
