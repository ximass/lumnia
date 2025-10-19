<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RerankingService
{
    protected string $baseUrl;
    protected string $model;
    protected int $timeout;
    protected string $provider;
    protected string $providerType;
    protected string $chatEndpoint;

    public function __construct()
    {
        $this->provider = config('providers.default_llm');
        
        $config = config("providers.llm.{$this->provider}", []);
        
        $this->baseUrl = $config['base_url'];
        $this->model = $config['model'];
        $this->timeout = $config['timeout'];
        $this->providerType = $config['type'];
        $this->chatEndpoint = $config['chat_endpoint'];
    }

    /**
     * Rerank chunks using LLM-based scoring
     * 
     * @param string $query The user query
     * @param array $chunks Array of chunk objects
     * @param int $topK Number of top chunks to return
     * @return array Reranked chunks
     */
    public function rerank(string $query, array $chunks, int $topK = 10): array
    {
        if (empty($chunks)) {
            return [];
        }

        Log::info('Starting reranking process', [
            'query' => $query,
            'chunk_count' => count($chunks),
            'top_k' => $topK
        ]);

        $scoredChunks = [];

        foreach ($chunks as $index => $chunk) {
            $relevanceScore = $this->scoreRelevance($query, $chunk->text);
            
            $scoredChunks[] = (object)[
                'id' => $chunk->id,
                'source_id' => $chunk->source_id,
                'text' => $chunk->text,
                'metadata' => $chunk->metadata ?? null,
                'semantic_score' => $chunk->semantic_score ?? 0,
                'lexical_score' => $chunk->lexical_score ?? 0,
                'combined_score' => $chunk->combined_score ?? 0,
                'rerank_score' => $relevanceScore,
                'original_position' => $index
            ];

            Log::debug('Chunk scored', [
                'index' => $index,
                'chunk_id' => $chunk->id,
                'original_score' => $chunk->combined_score ?? 0,
                'rerank_score' => $relevanceScore
            ]);
        }

        usort($scoredChunks, function($a, $b) {
            return $b->rerank_score <=> $a->rerank_score;
        });

        $topChunks = array_slice($scoredChunks, 0, $topK);

        Log::info('Reranking completed', [
            'original_count' => count($chunks),
            'returned_count' => count($topChunks),
            'top_scores' => array_map(fn($c) => $c->rerank_score, array_slice($topChunks, 0, 5))
        ]);

        return $topChunks;
    }

    /**
     * Score the relevance of a text chunk to a query using LLM
     * 
     * @param string $query The user query
     * @param string $text The chunk text
     * @return float Relevance score between 0 and 1
     */
    private function scoreRelevance(string $query, string $text): float
    {
        try {
            $prompt = $this->buildScoringPrompt($query, $text);
            $requestPayload = $this->buildRequestPayload($prompt, 10);
            
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->post($this->baseUrl . $this->chatEndpoint, $requestPayload);

            if (!$response->successful()) {
                Log::warning('Reranking score request failed', [
                    'provider' => $this->provider,
                    'status' => $response->status(),
                    'error' => $response->body()
                ]);
                return 0.5;
            }

            $responseData = $response->json();
            $content = $this->extractContent($responseData);
            
            $score = $this->extractScore($content);
            
            return max(0.0, min(1.0, $score));

        } catch (\Exception $e) {
            Log::error('Error scoring chunk relevance', [
                'provider' => $this->provider,
                'error' => $e->getMessage(),
                'query' => substr($query, 0, 100),
                'text' => substr($text, 0, 100)
            ]);
            return 0.5;
        }
    }

    /**
     * Build request payload based on provider type
     */
    private function buildRequestPayload(string $prompt, int $maxTokens = 10): array
    {
        $systemMessage = 'Você é um assistente especializado em avaliar a relevância de textos. Responda apenas com um número entre 0 e 1, onde 0 significa completamente irrelevante e 1 significa perfeitamente relevante.';
        
        if ($this->providerType === 'ollama') {
            return [
                'model' => $this->model,
                'prompt' => "{$systemMessage}\n\n{$prompt}",
                'stream' => false,
                'options' => [
                    'temperature' => 0.1,
                    'num_predict' => $maxTokens,
                ]
            ];
        }
        
        return [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemMessage
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.1,
            'max_tokens' => $maxTokens,
        ];
    }

    /**
     * Extract content from response based on provider type
     */
    private function extractContent(array $responseData): string
    {
        if ($this->providerType === 'ollama') {
            return $responseData['response'] ?? '0.5';
        }
        
        return $responseData['choices'][0]['message']['content'] ?? '0.5';
    }

    /**
     * Build the prompt for scoring relevance
     */
    private function buildScoringPrompt(string $query, string $text): string
    {
        return "Avalie a relevância do seguinte texto em relação à pergunta. Responda apenas com um número decimal entre 0.0 e 1.0.\n\n" .
               "Pergunta: {$query}\n\n" .
               "Texto: " . substr($text, 0, 500) . "\n\n" .
               "Relevância (0.0 a 1.0):";
    }

    /**
     * Extract numeric score from LLM response
     */
    private function extractScore(string $content): float
    {
        $content = trim($content);
        
        if (preg_match('/(\d+\.?\d*)/', $content, $matches)) {
            $score = floatval($matches[1]);
            
            if ($score > 1.0 && $score <= 10.0) {
                $score = $score / 10.0;
            } elseif ($score > 10.0 && $score <= 100.0) {
                $score = $score / 100.0;
            }
            
            return $score;
        }
        
        return 0.5;
    }

    /**
     * Batch rerank for better performance
     */
    public function rerankBatch(string $query, array $chunks, int $topK = 10, int $batchSize = 5): array
    {
        if (empty($chunks)) {
            return [];
        }

        Log::info('Starting batch reranking process', [
            'query' => $query,
            'chunk_count' => count($chunks),
            'top_k' => $topK,
            'batch_size' => $batchSize
        ]);

        $batches = array_chunk($chunks, $batchSize);
        $scoredChunks = [];

        foreach ($batches as $batchIndex => $batch) {
            $batchText = $this->buildBatchScoringPrompt($query, $batch);
            $scores = $this->scoreBatch($batchText, count($batch));

            foreach ($batch as $index => $chunk) {
                $relevanceScore = $scores[$index] ?? 0.5;
                
                $scoredChunks[] = (object)[
                    'id' => $chunk->id,
                    'source_id' => $chunk->source_id,
                    'text' => $chunk->text,
                    'metadata' => $chunk->metadata ?? null,
                    'semantic_score' => $chunk->semantic_score ?? 0,
                    'lexical_score' => $chunk->lexical_score ?? 0,
                    'combined_score' => $chunk->combined_score ?? 0,
                    'rerank_score' => $relevanceScore,
                    'original_position' => $batchIndex * $batchSize + $index
                ];
            }

            Log::debug('Batch scored', [
                'batch_index' => $batchIndex,
                'batch_size' => count($batch),
                'scores' => $scores
            ]);
        }

        usort($scoredChunks, function($a, $b) {
            return $b->rerank_score <=> $a->rerank_score;
        });

        $topChunks = array_slice($scoredChunks, 0, $topK);

        Log::info('Batch reranking completed', [
            'original_count' => count($chunks),
            'returned_count' => count($topChunks),
            'top_scores' => array_map(fn($c) => $c->rerank_score, array_slice($topChunks, 0, 5))
        ]);

        return $topChunks;
    }

    /**
     * Build prompt for batch scoring
     */
    private function buildBatchScoringPrompt(string $query, array $chunks): string
    {
        $prompt = "Avalie a relevância de cada texto em relação à pergunta. Responda apenas com os números separados por vírgula.\n\n";
        $prompt .= "Pergunta: {$query}\n\n";
        
        foreach ($chunks as $index => $chunk) {
            $text = substr($chunk->text, 0, 300);
            $prompt .= "Texto " . ($index + 1) . ": {$text}\n\n";
        }
        
        $prompt .= "Relevância de cada texto (0.0 a 1.0, separados por vírgula):";
        
        return $prompt;
    }

    /**
     * Score a batch of chunks
     */
    private function scoreBatch(string $prompt, int $expectedCount): array
    {
        try {
            $requestPayload = $this->buildBatchRequestPayload($prompt, 50);
            
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->post($this->baseUrl . $this->chatEndpoint, $requestPayload);

            if (!$response->successful()) {
                Log::warning('Batch reranking score request failed', [
                    'provider' => $this->provider,
                    'status' => $response->status(),
                    'error' => $response->body()
                ]);
                return array_fill(0, $expectedCount, 0.5);
            }

            $responseData = $response->json();
            $content = $this->extractContent($responseData);
            
            $scores = $this->extractBatchScores($content, $expectedCount);
            
            return $scores;

        } catch (\Exception $e) {
            Log::error('Error scoring batch', [
                'provider' => $this->provider,
                'error' => $e->getMessage(),
                'expected_count' => $expectedCount
            ]);
            return array_fill(0, $expectedCount, 0.5);
        }
    }

    /**
     * Build request payload for batch scoring
     */
    private function buildBatchRequestPayload(string $prompt, int $maxTokens = 50): array
    {
        $systemMessage = 'Você é um assistente especializado em avaliar a relevância de textos. Responda apenas com números entre 0 e 1 separados por vírgula.';
        
        if ($this->providerType === 'ollama') {
            return [
                'model' => $this->model,
                'prompt' => "{$systemMessage}\n\n{$prompt}",
                'stream' => false,
                'options' => [
                    'temperature' => 0.1,
                    'num_predict' => $maxTokens,
                ]
            ];
        }
        
        return [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemMessage
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.1,
            'max_tokens' => $maxTokens,
        ];
    }

    /**
     * Extract scores from batch response
     */
    private function extractBatchScores(string $content, int $expectedCount): array
    {
        $scores = [];
        
        if (preg_match_all('/(\d+\.?\d*)/', $content, $matches)) {
            foreach ($matches[1] as $match) {
                $score = floatval($match);
                
                if ($score > 1.0 && $score <= 10.0) {
                    $score = $score / 10.0;
                } elseif ($score > 10.0 && $score <= 100.0) {
                    $score = $score / 100.0;
                }
                
                $scores[] = max(0.0, min(1.0, $score));
            }
        }
        
        while (count($scores) < $expectedCount) {
            $scores[] = 0.5;
        }
        
        return array_slice($scores, 0, $expectedCount);
    }
}
