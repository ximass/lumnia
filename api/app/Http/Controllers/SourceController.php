<?php

namespace App\Http\Controllers;

use App\Models\Source;
use App\Models\KnowledgeBase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class SourceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $kbId = $request->get('kb_id');
        
        $query = Source::with(['knowledgeBase', 'chunks']);
        
        if ($kbId) {
            $query->where('kb_id', $kbId);
        }
        
        $sources = $query->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $sources
        ]);
    }

    public function create()
    {
        //
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kb_id' => 'required|uuid|exists:knowledge_bases,id',
            'source_type' => 'required|string|max:255',
            'source_identifier' => 'required|string|max:255',
            'content_hash' => 'required|string|max:255',
            'status' => 'required|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        $validated['id'] = Str::uuid();

        $source = Source::create($validated);
        $source->load(['knowledgeBase', 'chunks']);

        return response()->json([
            'status' => 'success',
            'message' => 'Source created successfully',
            'data' => $source
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $source = Source::with(['knowledgeBase', 'chunks'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $source
        ]);
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $source = Source::findOrFail($id);

        $validated = $request->validate([
            'source_type' => 'sometimes|string|max:255',
            'source_identifier' => 'sometimes|string|max:255',
            'content_hash' => 'sometimes|string|max:255',
            'status' => 'sometimes|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        $source->update($validated);
        $source->load(['knowledgeBase', 'chunks']);

        return response()->json([
            'status' => 'success',
            'message' => 'Source updated successfully',
            'data' => $source
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $source = Source::findOrFail($id);
        $source->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Source deleted successfully'
        ]);
    }
}
