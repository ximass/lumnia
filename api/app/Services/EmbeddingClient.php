<?php

namespace App\Services;

use App\Contracts\EmbeddingProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingClient
{
    private EmbeddingProvider $provider;

    public function __construct(EmbeddingProvider $provider)
    {
        $this->provider = $provider;
    }

    public function getEmbeddings(array $texts): array
    {
        if (empty($texts)) {
            return [];
        }

        $batchSize = $this->provider->getBatchSize();
        $batches = array_chunk($texts, $batchSize);
        $allEmbeddings = [];

        foreach ($batches as $batch) {
            $embeddings = $this->processBatch($batch);
            $allEmbeddings = array_merge($allEmbeddings, $embeddings);
        }

        return $allEmbeddings;
    }

    private function processBatch(array $texts): array
    {
        $maxRetries = $this->provider->getMaxRetries();
        $retryDelay = $this->provider->getRetryDelay();

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            try {
                return $this->provider->getEmbeddings($texts);
            } catch (ConnectionException | RequestException $e) {
                if ($attempt === $maxRetries) {
                    Log::error('Max retries reached for embedding request', [
                        'attempt' => $attempt + 1,
                        'error' => $e->getMessage(),
                        'texts_count' => count($texts)
                    ]);
                    throw $e;
                }

                $delay = $this->calculateExponentialBackoff($attempt, $retryDelay);
                Log::warning('Retrying embedding request', [
                    'attempt' => $attempt + 1,
                    'delay' => $delay,
                    'error' => $e->getMessage()
                ]);

                sleep($delay);
            }
        }

        return [];
    }

    private function calculateExponentialBackoff(int $attempt, int $baseDelay): int
    {
        return $baseDelay * pow(2, $attempt);
    }
}
