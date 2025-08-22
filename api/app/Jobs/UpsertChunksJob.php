<?php

namespace App\Jobs;

use App\Models\Chunk;
use App\Models\Source;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpsertChunksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $sourceId;
    protected array $chunks;
    
    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(string $sourceId, array $chunks)
    {
        $this->sourceId = $sourceId;
        $this->chunks = $chunks;
    }

    public function handle(): void
    {
        $source = Source::findOrFail($this->sourceId);

        try {
            DB::transaction(function () use ($source) {
                $upsertedCount = 0;
                $skippedCount = 0;

                foreach ($this->chunks as $chunkData) {
                    $chunkId = $chunkData['chunk_id'];
                    
                    $existingChunk = Chunk::find($chunkId);
                    
                    if ($existingChunk) {
                        Log::debug('Chunk already exists, skipping', ['chunk_id' => $chunkId]);
                        $skippedCount++;
                        continue;
                    }

                    $chunk = Chunk::create([
                        'id' => $chunkId,
                        'source_id' => $this->sourceId,
                        'kb_id' => $source->kb_id,
                        'chunk_index' => $chunkData['chunk_index'],
                        'text' => $chunkData['text'],
                        'metadata' => array_merge(
                            $chunkData['metadata'] ?? [],
                            [
                                'offset_tokens' => $chunkData['offset_tokens'] ?? null,
                                'token_count' => $chunkData['token_count'] ?? null,
                                'embedding' => $chunkData['embedding'] ?? null,
                            ]
                        ),
                    ]);

                    if (isset($chunkData['embedding'])) {
                        $this->updateEmbedding($chunk, $chunkData['embedding']);
                    }

                    $this->updateTsVector($chunkId, $chunkData['text']);
                    
                    $upsertedCount++;
                }

                $this->updateSourceProgress($source, $upsertedCount, $skippedCount);
            });

            Log::info('Chunks upserted successfully', [
                'source_id' => $this->sourceId,
                'chunks_processed' => count($this->chunks)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to upsert chunks', [
                'source_id' => $this->sourceId,
                'chunks_count' => count($this->chunks),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function updateEmbedding(Chunk $chunk, array $embedding): void
    {
        $embeddingVector = '[' . implode(',', $embedding) . ']';
        
        DB::statement(
            'UPDATE chunks SET embedding = ?::vector WHERE id = ?',
            [$embeddingVector, $chunk->id]
        );
    }

    private function updateTsVector(string $chunkId, string $text): void
    {
        DB::statement(
            'UPDATE chunks SET tsv = to_tsvector(?, ?) WHERE id = ?',
            [config('search.language'), $text, $chunkId]
        );
    }

    private function updateSourceProgress(Source $source, int $upsertedCount, int $skippedCount): void
    {
        $totalChunks = Chunk::where('source_id', $this->sourceId)->count();
        
        if ($totalChunks > 0) {
            $source->update([
                'status' => 'processed',
                'metadata' => array_merge($source->metadata ?? [], [
                    'total_chunks' => $totalChunks,
                    'last_processed_at' => now()->toISOString(),
                    'processing_stats' => [
                        'upserted' => $upsertedCount,
                        'skipped' => $skippedCount,
                    ]
                ])
            ]);
        } else {
            $source->update(['status' => 'failed']);
            throw new \Exception('No chunks were created for source');
        }
    }

    public function failed(\Throwable $exception): void
    {
        try {
            Source::where('id', $this->sourceId)->update(['status' => 'upsert_failed']);
        } catch (\Exception $e) {
            Log::error('Failed to update source status after job failure', [
                'source_id' => $this->sourceId,
                'error' => $e->getMessage()
            ]);
        }
        
        Log::error('UpsertChunksJob failed permanently', [
            'source_id' => $this->sourceId,
            'chunks_count' => count($this->chunks),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
