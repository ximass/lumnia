<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\KnowledgeBase;
use App\Models\Source;
use App\Models\Chunk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class SearchControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_requires_authentication()
    {
        $response = $this->postJson('/api/search', [
            'kb_id' => Str::uuid(),
            'query' => 'test query'
        ]);

        $response->assertStatus(401);
    }

    public function test_search_validates_required_fields()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/search', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['kb_id', 'query']);
    }

    public function test_search_validates_kb_id_exists()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/search', [
                'kb_id' => Str::uuid(),
                'query' => 'test query'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['kb_id']);
    }

    public function test_search_returns_expected_structure()
    {
        $this->markTestIncomplete('Requires actual embedding service to be running');
        
        // This test would need a running embedding service
        // and proper test data setup with embeddings
        
        $user = User::factory()->create();
        $kb = KnowledgeBase::factory()->create(['owner_id' => $user->id]);
        
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/search', [
                'kb_id' => $kb->id,
                'query' => 'test query'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'answer',
                    'candidate_chunks',
                    'prompt'
                ]
            ]);
    }
}
