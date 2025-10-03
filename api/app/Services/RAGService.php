<?php

namespace App\Services;

use App\Services\EmbeddingClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RAGService
{
    protected EmbeddingClient $embeddingClient;

    public function __construct(EmbeddingClient $embeddingClient)
    {
        $this->embeddingClient = $embeddingClient;
    }

    public function retrieveRelevantChunks(string $query, ?string $kbId, int $topK = 5, float $threshold = 0.3): array
    {
        if (!$kbId) {
            Log::info('No knowledge base provided for RAG retrieval');
            return [];
        }

        try {
            Log::info('RAG retrieval started', [
                'query' => $query,
                'kb_id' => $kbId,
                'top_k' => $topK,
                'threshold' => $threshold
            ]);

            $queryEmbeddings = $this->embeddingClient->getEmbeddings([$query]);
            
            if (empty($queryEmbeddings)) {
                Log::error('Failed to generate query embedding for RAG');
                return [];
            }

            $queryEmbedding = $queryEmbeddings[0];
            
            $semanticWeight = config('search.scoring.semantic_weight', 0.7);
            $lexicalWeight = config('search.scoring.lexical_weight', 0.3);
            $maxChunks = 50; // Reasonable limit for RAG context

            $chunks = $this->executeHybridSearch(
                $kbId,
                $query,
                $queryEmbedding,
                $semanticWeight,
                $lexicalWeight,
                $maxChunks
            );

            $relevantChunks = $chunks->filter(function ($chunk) use ($threshold) {
                return $chunk->combined_score >= $threshold;
            })->take($topK)->toArray();

            Log::info('RAG retrieval completed', [
                'found_chunks' => count($relevantChunks),
                'scores' => array_map(fn($chunk) => $chunk->combined_score, $relevantChunks)
            ]);

            return $relevantChunks;

        } catch (\Exception $e) {
            Log::error('RAG retrieval failed', [
                'query' => $query,
                'kb_id' => $kbId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function buildRAGPrompt(string $userMessage, array $chunks, ?string $personaInstructions = null): string
    {
        $prompt  = "Você deve formular uma resposta à pergunta do usuário usando EXCLUSIVAMENTE informações fornecidas no contexto abaixo.";

        if ($personaInstructions) {
            $prompt .= "=== INSTRUÇÕES DO SISTEMA ===\n";
            $prompt .= $personaInstructions . "\n\n";
            $prompt .= "---\n\n";
        }

        if (!empty($chunks)) {
            $prompt .= "=== CONTEXTO ===\n\n";
            
            foreach ($chunks as $i => $chunk) {
                $sourceInfo = '';
                if (isset($chunk->metadata)) {
                    $metadata = is_string($chunk->metadata) ? json_decode($chunk->metadata, true) : $chunk->metadata;
                    if (isset($metadata['source_name'])) {
                        $sourceInfo = " (Fonte: " . $metadata['source_name'] . ")";
                    }
                }
                $prompt .= "Trecho " . ($i + 1) . $sourceInfo . ":\n";
                $prompt .= $chunk->text . "\n\n";
            }
            $prompt .= "=== FIM DO CONTEXTO ===\n\n";
        }

        $prompt .= "=== PERGUNTA DO USUÁRIO ===\n";
        $prompt .= $userMessage . "\n\n";
        $prompt .= "=== FIM DA PERGUNTA ===\n\n";

        $prompt .= "=== INSTRUÇÕES DE RESPOSTA ===\n";
        if (!empty($chunks)) {
            $prompt .= "1. Formule a sua resposta utilizando apenas informações fornecidas no contexto acima\n";
            $prompt .= "2. Se a resposta estiver no contexto, forneça uma resposta clara, direta e completa\n";
            $prompt .= "3. Se o contexto não contiver informação suficiente para responder, diga claramente: \"Não encontrei informações suficientes na base de conhecimento para responder essa pergunta\"\n";
            $prompt .= "4. NÃO invente, extrapole ou use conhecimento externo ao contexto fornecido\n";
        } else {
            $prompt .= "Não foram encontradas informações relevantes na base de conhecimento.\n";
            $prompt .= "Informe ao usuário que não há informações disponíveis sobre este assunto na base de conhecimento atual.\n";
        }

        $prompt .= "=== FIM DAS INSTRUÇÕES ===";

        return $prompt;
    }

    private function executeHybridSearch(
        string $kbId,
        string $query,
        array $queryEmbedding,
        float $semanticWeight,
        float $lexicalWeight,
        int $maxChunks
    ) {
        $embeddingStr = '[' . implode(',', $queryEmbedding) . ']';

        $sql = "
            WITH semantic_search AS (
                SELECT 
                    id,
                    source_id,
                    text,
                    metadata,
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
                    ts_rank_cd(
                        tsv, 
                        plainto_tsquery(?, ?),
                        32
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
}
