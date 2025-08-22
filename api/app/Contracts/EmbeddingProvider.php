<?php

namespace App\Contracts;

interface EmbeddingProvider
{
    public function getEmbeddings(array $texts): array;
    
    public function getBatchSize(): int;
    
    public function getMaxRetries(): int;
    
    public function getRetryDelay(): int;
}
