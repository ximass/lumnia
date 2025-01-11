<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Group;

class GroupController extends Controller
{
    public function index()
    {
        return response()->json(Group::with(['users', 'knowledgeBases'])->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'user_ids' => 'array',
            'user_ids.*' => 'exists:users,id',
            'knowledge_base_ids' => 'array',
            'knowledge_base_ids.*' => 'exists:knowledge_bases,id',
        ]);

        $group = Group::create($request->only('name'));
        
        if ($request->has('user_ids')) {
            $group->users()->attach($request->user_ids);
        }

        if ($request->has('knowledge_base_ids')) {
            $group->knowledgeBases()->attach($request->knowledge_base_ids);
        }

        return response()->json($group->load('users', 'knowledgeBases'), 201);
    }

    public function show(Group $group)
    {
        return response()->json($group->load(['users', 'knowledgeBases']));
    }

    public function update(Request $request, Group $group)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'user_ids' => 'array',
            'user_ids.*' => 'exists:users,id',
            'knowledge_base_ids' => 'array',
            'knowledge_base_ids.*' => 'exists:knowledge_bases,id',
        ]);

        $group->update($request->only('name'));

        if ($request->has('user_ids')) {
            $group->users()->sync($request->user_ids);
        }

        if ($request->has('knowledge_base_ids')) {
            $group->knowledgeBases()->sync($request->knowledge_base_ids);
        }

        return response()->json($group->load(['users', 'knowledgeBases']));
    }

    public function destroy(Group $group)
    {
        $group->delete();

        return response()->json(['message' => 'Group deleted successfully']);
    }
}