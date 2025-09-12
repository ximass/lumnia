<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\KnowledgeBase;

class KnowledgeBaseController extends Controller
{
    public function index()
    {
        $knowledgeBases = KnowledgeBase::with(['owner', 'sources', 'chunks'])->get();
        return response()->json([
            'status' => 'success',
            'data' => $knowledgeBases
        ]);
    }

    public function getKnowledgeBases(Request $request)
    {
        $userId = $request->input('user_id');
        $knowledgeBases = [];
        if ($userId) {
            // Buscar apenas as bases de conhecimento vinculadas a grupos nos quais o usuário está cadastrado
            $knowledgeBases = KnowledgeBase::with(['owner', 'sources', 'chunks'])
                ->whereHas('groups.users', function ($q) use ($userId) {
                    $q->where('users.id', $userId);
                })
                ->get();
        }

        return response()->json([
            'status' => 'success',
            'data' => $knowledgeBases
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'owner_id' => 'required|exists:users,id',
        ]);

        $knowledgeBase = KnowledgeBase::create([
            'id' => Str::uuid(),
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'owner_id' => $request->input('owner_id'),
        ]);

        $knowledgeBase->load(['owner', 'sources', 'chunks']);

        return response()->json([
            'status' => 'success',
            'message' => 'Knowledge base created successfully',
            'data' => $knowledgeBase
        ], 201);
    }

    public function show(KnowledgeBase $knowledgeBase)
    {
        $knowledgeBase->load(['owner', 'sources', 'chunks']);

        return response()->json([
            'status' => 'success',
            'data' => $knowledgeBase
        ]);
    }

    public function update(Request $request, KnowledgeBase $knowledgeBase)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $knowledgeBase->update($request->only(['name', 'description']));
        $knowledgeBase->load(['owner', 'sources', 'chunks']);

        return response()->json([
            'status' => 'success',
            'message' => 'Knowledge base updated successfully',
            'data' => $knowledgeBase
        ]);
    }

    public function updateKnowledgeBase(Request $request, KnowledgeBase $knowledgeBase)
    {
        return $this->update($request, $knowledgeBase);
    }

    public function destroy(KnowledgeBase $knowledgeBase)
    {
        $knowledgeBase->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Knowledge base deleted successfully'
        ]);
    }
}
