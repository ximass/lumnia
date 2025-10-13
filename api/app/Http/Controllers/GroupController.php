<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Group;

class GroupController extends Controller
{
    public function index()
    {
        return response()->json(Group::with([ 'knowledgeBases', 'permissions'])->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'knowledge_base_ids' => 'array',
            'knowledge_base_ids.*' => 'exists:knowledge_bases,id',
            'permission_ids' => 'array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        $group = Group::create($request->only('name'));

        if ($request->has(key: 'knowledge_base_ids')) {
            $group->knowledgeBases()->attach($request->knowledge_base_ids);
        }

        if ($request->has('permission_ids')) {
            $group->permissions()->attach($request->permission_ids);
        }

        return response()->json($group->load(['knowledgeBases']), 201);
    }

    public function show(Group $group)
    {
        return response()->json($group->load(['users', 'knowledgeBases', 'permissions']));
    }

    public function update(Request $request, Group $group)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'knowledge_base_ids' => 'array',
            'knowledge_base_ids.*' => 'exists:knowledge_bases,id',
            'permission_ids' => 'array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        $group->update($request->only('name'));

        if ($request->has('knowledge_base_ids')) {
            $group->knowledgeBases()->sync($request->knowledge_base_ids);
        }

        if ($request->has('permission_ids')) {
            $group->permissions()->sync($request->permission_ids);
        }

        return response()->json($group->load(['knowledgeBases']));
    }

    public function destroy(Group $group)
    {
        $group->delete();

        return response()->json(['message' => 'Group deleted successfully']);
    }

    public function search(Request $request)
    {

        $search = $request->query('search', '');

        $groups = Group::where('name', 'ILIKE', "%{$search}%")
                     ->select('id', 'name')
                     ->limit(10)
                     ->get();

        return response()->json($groups);
    }
}
