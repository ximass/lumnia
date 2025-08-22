<?php

namespace App\Http\Controllers;

use App\Models\Chunk;
use App\Models\Source;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ChunkController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $kbId = $request->get('kb_id');
        $sourceId = $request->get('source_id');
        
        $query = Chunk::with(['source', 'knowledgeBase']);
        
        if ($kbId) {
            $query->where('kb_id', $kbId);
        }
        
        if ($sourceId) {
            $query->where('source_id', $sourceId);
        }
        
        $chunks = $query->orderBy('chunk_index')->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $chunks
        ]);
    }

    public function create()
    {
        //
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|string|unique:chunks,id',
            'source_id' => 'required|uuid|exists:sources,id',
            'kb_id' => 'required|uuid|exists:knowledge_bases,id',
            'chunk_index' => 'required|integer|min:0',
            'text' => 'required|string',
            'metadata' => 'nullable|array',
        ]);

        $chunk = Chunk::create($validated);
        
        // Update tsvector for full-text search
        DB::statement('UPDATE chunks SET tsv = to_tsvector(\'english\', text) WHERE id = ?', [$chunk->id]);
        
        $chunk->load(['source', 'knowledgeBase']);

        return response()->json([
            'status' => 'success',
            'message' => 'Chunk created successfully',
            'data' => $chunk
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $chunk = Chunk::with(['source', 'knowledgeBase'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $chunk
        ]);
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $chunk = Chunk::findOrFail($id);

        $validated = $request->validate([
            'chunk_index' => 'sometimes|integer|min:0',
            'text' => 'sometimes|string',
            'metadata' => 'nullable|array',
        ]);

        $chunk->update($validated);
        
        // Update tsvector if text was changed
        if (isset($validated['text'])) {
            DB::statement('UPDATE chunks SET tsv = to_tsvector(\'english\', text) WHERE id = ?', [$chunk->id]);
        }
        
        $chunk->load(['source', 'knowledgeBase']);

        return response()->json([
            'status' => 'success',
            'message' => 'Chunk updated successfully',
            'data' => $chunk
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $chunk = Chunk::findOrFail($id);
        $chunk->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Chunk deleted successfully'
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string',
            'kb_id' => 'sometimes|uuid|exists:knowledge_bases,id',
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        $query = $validated['query'];
        $kbId = $validated['kb_id'] ?? null;
        $limit = $validated['limit'] ?? 10;

        $chunksQuery = Chunk::with(['source', 'knowledgeBase'])
            ->whereRaw('tsv @@ plainto_tsquery(\'english\', ?)', [$query])
            ->orderByRaw('ts_rank(tsv, plainto_tsquery(\'english\', ?)) DESC', [$query]);

        if ($kbId) {
            $chunksQuery->where('kb_id', $kbId);
        }

        $chunks = $chunksQuery->limit($limit)->get();

        return response()->json([
            'status' => 'success',
            'data' => $chunks
        ]);
    }
}
