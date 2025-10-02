<?php

namespace App\Jobs;

use App\Models\Source;
use App\Services\EmbeddingClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ChunkAndEmbedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $sourceId;
    protected array $chunks;
    
    public int $tries = 3;
    public int $backoff = 120;

    public function __construct(string $sourceId, array $chunks)
    {
        $this->sourceId = $sourceId;
        $this->chunks = $chunks;
    }

    public function handle(EmbeddingClient $embeddingClient): void
    {
        $source = Source::findOrFail($this->sourceId);
        
        if ($source->status === 'processed') {
            Log::info('Source already processed, skipping embedding', ['source_id' => $this->sourceId]);
            return;
        }

        try {
            $source->update(['status' => 'embedding']);

            $existingChunkIds = $this->getExistingChunkIds();
            $chunksToProcess = $this->filterNewChunks($existingChunkIds);

            if (empty($chunksToProcess)) {
                Log::info('All chunks already exist, skipping to upsert', ['source_id' => $this->sourceId]);
                UpsertChunksJob::dispatch($this->sourceId, $this->chunks);
                return;
            }

            $provider = config('providers.default_embedding', 'lm_studio');
            $batchSize = config("providers.embedding.{$provider}.batch_size", 10);
            $chunkBatches = array_chunk($chunksToProcess, $batchSize);

            foreach ($chunkBatches as $batch) {
                $texts = array_column($batch, 'text');
                
                try {
                    $embeddings = $embeddingClient->getEmbeddings($texts);
                    
                    if (count($embeddings) !== count($texts)) {
                        throw new \Exception('Embedding count mismatch');
                    }

                    foreach ($batch as $index => $chunk) {
                        $batch[$index]['embedding'] = $embeddings[$index];
                    }

                    UpsertChunksJob::dispatch($this->sourceId, $batch);

                } catch (\Exception $e) {
                    Log::error('Failed to generate embeddings for batch', [
                        'source_id' => $this->sourceId,
                        'batch_size' => count($batch),
                        'error' => $e->getMessage()
                    ]);
                    throw $e;
                }
            }

            $source->update(['status' => 'embedding_complete']);

            Log::info('Chunk embedding completed', [
                'source_id' => $this->sourceId,
                'total_chunks' => count($this->chunks),
                'processed_chunks' => count($chunksToProcess),
                'batches' => count($chunkBatches)
            ]);

        } catch (\Exception $e) {
            $source->update(['status' => 'embedding_failed']);
            Log::error('Failed to embed chunks', [
                'source_id' => $this->sourceId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function getExistingChunkIds(): array
    {
        return \App\Models\Chunk::where('source_id', $this->sourceId)
            ->pluck('id')
            ->toArray();
    }

    private function filterNewChunks(array $existingChunkIds): array
    {
        return array_filter($this->chunks, function ($chunk) use ($existingChunkIds) {
            return !in_array($chunk['chunk_id'], $existingChunkIds);
        });
    }

    public function failed(\Throwable $exception): void
    {
        Source::where('id', $this->sourceId)->update(['status' => 'embedding_failed']);
        
        Log::error('ChunkAndEmbedJob failed permanently', [
            'source_id' => $this->sourceId,
            'chunks_count' => count($this->chunks),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
