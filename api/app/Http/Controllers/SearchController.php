<?php

namespace App\Http\Controllers;

use App\Services\EmbeddingClient;
use App\Services\RerankingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    protected EmbeddingClient $embeddingClient;
    protected RerankingService $rerankingService;

    public function __construct(EmbeddingClient $embeddingClient, RerankingService $rerankingService)
    {
        $this->embeddingClient = $embeddingClient;
        $this->rerankingService = $rerankingService;
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kb_id' => 'required|uuid|exists:knowledge_bases,id',
            'query' => 'required|string|min:3|max:1000',
        ]);

        try {
            $kbId = $validated['kb_id'];
            $query = $validated['query'];

            // Get query embedding
            $queryEmbeddings = $this->embeddingClient->getEmbeddings([$query]);
            
            if (empty($queryEmbeddings)) {
                throw new \Exception('Failed to generate query embedding');
            }

            $queryEmbedding = $queryEmbeddings[0];
            
            // Get search configuration
            $semanticWeight = config('search.scoring.semantic_weight', 0.6);
            $lexicalWeight = config('search.scoring.lexical_weight', 0.4);
            $maxChunks = config('search.scoring.max_chunks', 100);
            $enableReranking = config('search.scoring.enable_reranking', false);
            $rerankTopK = config('search.scoring.rerank_top_k', 10);

            // Execute hybrid search SQL
            $chunks = $this->executeHybridSearch(
                $kbId,
                $query,
                $queryEmbedding,
                $semanticWeight,
                $lexicalWeight,
                $maxChunks
            );

            // Apply reranking if enabled
            if ($enableReranking && count($chunks) > 0) {
                $chunks = $this->applyReranking($query, $chunks, $rerankTopK);
            }

            // Build candidate chunks payload
            $candidateChunks = array_map(function ($chunk) {
                return [
                    'id' => $chunk->id,
                    'text' => $chunk->text,
                    'source_id' => $chunk->source_id,
                    'score' => $chunk->combined_score,
                ];
            }, $chunks);

            // Build search prompt context
            $prompt = $this->buildSearchPrompt($query, $candidateChunks);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'answer' => null,
                    'candidate_chunks' => $candidateChunks,
                    'prompt' => $prompt,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Search failed', [
                'kb_id' => $validated['kb_id'] ?? null,
                'query' => $validated['query'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Search failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Execute hybrid search combining semantic and lexical search
     */
    private function executeHybridSearch(
        string $kbId,
        string $query,
        array $queryEmbedding,
        float $semanticWeight,
        float $lexicalWeight,
        int $maxChunks
    ) {
        // Convert embedding array to PostgreSQL vector format
        $embeddingStr = '[' . implode(',', $queryEmbedding) . ']';

        $sql = "
            WITH semantic_search AS (
                SELECT 
                    id,
                    source_id,
                    text,
                    metadata,
                    -- Cosine similarity (1 - cosine distance)
                    (1 - (embedding <=> ?::vector)) AS semantic_score
                FROM chunks 
                WHERE kb_id = ? 
                    AND embedding IS NOT NULL
                ORDER BY embedding <=> ?::vector
                LIMIT ?
            ),
            lexical_search AS (
                SELECT 
                    id,
                    source_id,
                    text,
                    metadata,
                    -- Normalized BM25 score using ts_rank_cd
                    ts_rank_cd(
                        tsv, 
                        plainto_tsquery(?, ?),
                        32 -- normalization flag: divide by document length
                    ) AS lexical_score
                FROM chunks 
                WHERE kb_id = ? 
                    AND tsv @@ plainto_tsquery(?, ?)
                ORDER BY lexical_score DESC
                LIMIT ?
            ),
            combined_results AS (
                SELECT 
                    COALESCE(s.id, l.id) as id,
                    COALESCE(s.source_id, l.source_id) as source_id,
                    COALESCE(s.text, l.text) as text,
                    COALESCE(s.metadata, l.metadata) as metadata,
                    COALESCE(s.semantic_score, 0.0) as semantic_score,
                    COALESCE(l.lexical_score, 0.0) as lexical_score,
                    -- Combined score with configurable weights
                    (? * COALESCE(s.semantic_score, 0.0) + ? * COALESCE(l.lexical_score, 0.0)) as combined_score
                FROM semantic_search s
                FULL OUTER JOIN lexical_search l ON s.id = l.id
            )
            SELECT 
                id,
                source_id,
                text,
                metadata,
                semantic_score,
                lexical_score,
                combined_score
            FROM combined_results
            WHERE combined_score > 0
            ORDER BY combined_score DESC
            LIMIT ?
        ";

        return collect(DB::select($sql, [
            $embeddingStr,      // semantic search embedding 1
            $kbId,              // semantic search kb_id
            $embeddingStr,      // semantic search embedding 2 (for ORDER BY)
            $maxChunks,         // semantic search limit
            config('search.language'), // lexical search language 1
            $query,             // lexical search query 1
            $kbId,              // lexical search kb_id
            config('search.language'), // lexical search language 2
            $query,             // lexical search query 2
            $maxChunks,         // lexical search limit
            $semanticWeight,    // alpha weight
            $lexicalWeight,     // beta weight
            $maxChunks          // final limit
        ]));
    }

    /**
     * Apply cross-encoder reranking to top chunks
     */
    private function applyReranking(string $query, $chunks, int $topK): array
    {
        try {
            $useBatchReranking = config('search.scoring.rerank_use_batch', true);
            $batchSize = config('search.scoring.rerank_batch_size', 5);
            
            if ($useBatchReranking) {
                return $this->rerankingService->rerankBatch($query, $chunks, $topK, $batchSize);
            } else {
                return $this->rerankingService->rerank($query, $chunks, $topK);
            }
        } catch (\Exception $e) {
            Log::error('Reranking failed, returning original chunks', [
                'error' => $e->getMessage(),
                'query' => $query,
                'chunk_count' => count($chunks)
            ]);
            
            return array_slice($chunks, 0, $topK);
        }
    }

    /**
     * Build search prompt with context from candidate chunks
     */
    private function buildSearchPrompt(string $query, array $candidateChunks): string
    {
        $context = '';
        
        if (!empty($candidateChunks)) {
            $context = "Contexto relevante:\n\n";
            foreach ($candidateChunks as $i => $chunk) {
                $context .= "[" . ($i + 1) . "] " . $chunk['text'] . "\n\n";
            }
        }

        $prompt = $context . "Pergunta: " . $query . "\n\n";
        $prompt .= "Responda à pergunta baseando-se no contexto fornecido acima. ";
        $prompt .= "Se a informação não estiver disponível no contexto, informe que não possui informações suficientes.";

        return $prompt;
    }
}
