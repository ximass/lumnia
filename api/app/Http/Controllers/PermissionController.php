<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PermissionController extends Controller
{
    public function index()
    {
        return response()->json(["status" => "success", "data" => Permission::all()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:permissions,name',
            'label' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $permission = Permission::create($data);
        return response()->json(["status" => "success", "data" => $permission], Response::HTTP_CREATED);
    }

    public function show(Permission $permission)
    {
        return response()->json(["status" => "success", "data" => $permission]);
    }

    public function update(Request $request, Permission $permission)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:permissions,name,' . $permission->id,
            'label' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $permission->update($data);
        return response()->json(["status" => "success", "data" => $permission]);
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return response()->json(["status" => "success"]);
    }

    public function assignToGroup(Request $request, Permission $permission)
    {
        $request->validate(['group_id' => 'required|integer|exists:groups,id']);
        $group = Group::findOrFail($request->group_id);
        $group->permissions()->syncWithoutDetaching([$permission->id]);
        return response()->json(["status" => "success"]);
    }

    public function removeFromGroup(Request $request, Permission $permission)
    {
        $request->validate(['group_id' => 'required|integer|exists:groups,id']);
        $group = Group::findOrFail($request->group_id);
        $group->permissions()->detach($permission->id);
        return response()->json(["status" => "success"]);
    }
}
