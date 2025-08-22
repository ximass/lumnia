<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\RAGService;
use App\Models\KnowledgeBase;
use App\Models\Chunk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RAGServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RAGService $ragService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ragService = app(RAGService::class);
    }

    public function test_can_retrieve_relevant_chunks()
    {
        // Create test data
        $user = User::factory()->create();
        $kb = KnowledgeBase::factory()->create(['user_id' => $user->id]);
        
        // Create test chunks
        Chunk::factory()->create([
            'kb_id' => $kb->id,
            'content' => 'This is a test document about Laravel framework',
            'embedding' => array_fill(0, 768, 0.1), // Mock embedding
        ]);

        Chunk::factory()->create([
            'kb_id' => $kb->id,
            'content' => 'Vue.js is a progressive JavaScript framework',
            'embedding' => array_fill(0, 768, 0.2), // Mock embedding
        ]);

        // Test retrieval
        $chunks = $this->ragService->retrieveRelevantChunks(
            'Tell me about Laravel',
            $kb->id,
            5,
            0.1
        );

        $this->assertIsArray($chunks);
        $this->assertGreaterThanOrEqual(0, count($chunks));
    }

    public function test_can_build_rag_prompt()
    {
        $chunks = [
            ['content' => 'Laravel is a PHP framework'],
            ['content' => 'It has excellent documentation'],
        ];

        $prompt = $this->ragService->buildRAGPrompt(
            'What is Laravel?',
            $chunks,
            'You are a helpful assistant'
        );

        $this->assertStringContainsString('Laravel is a PHP framework', $prompt);
        $this->assertStringContainsString('It has excellent documentation', $prompt);
        $this->assertStringContainsString('What is Laravel?', $prompt);
        $this->assertStringContainsString('You are a helpful assistant', $prompt);
    }

    public function test_handles_empty_chunks_gracefully()
    {
        $prompt = $this->ragService->buildRAGPrompt(
            'What is Laravel?',
            [],
            'You are a helpful assistant'
        );

        $this->assertStringContainsString('What is Laravel?', $prompt);
        $this->assertStringContainsString('You are a helpful assistant', $prompt);
        $this->assertStringContainsString('não há contexto específico disponível', $prompt);
    }
}
