<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email',
            'admin' => 'sometimes|boolean'
        ]);

        // Update email only if provided
        if ($request->has('email')) {
            $updateData['email'] = $request->input('email');
        }

        // Update admin only if provided
        if ($request->has('admin')) {
            $updateData['admin'] = $request->input('admin');
        }

        $user = User::create($request->all());

        return response()->json($user, 200);
        
    }

    public function search(Request $request)
    {
        $request->validate([
            'search' => 'required|string'
        ]);

        $search = $request->query('search', '');
        
        $users = User::where('name', 'ILIKE', "%{$search}%")
                     ->select('id', 'name', 'avatar')
                     ->limit(10)
                     ->get();

        return response()->json($users);
    }

    public function show(User $user)
    {
        return response()->json($user);
    }

    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'admin' => 'sometimes|boolean',
            'avatar' => 'nullable|file|image|mimes:jpeg,png,jpg,gif|max:5120' // 5MB max
        ]);

        $updateData = [
            'name' => $request->input('name'),
        ];

        // Update email only if provided
        if ($request->has('email')) {
            $updateData['email'] = $request->input('email');
        }

        // Update admin only if provided
        if ($request->has('admin')) {
            $updateData['admin'] = $request->input('admin');
        }

        // Handle avatar upload if provided
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar && file_exists(public_path($user->avatar))) {
                unlink(public_path($user->avatar));
            }

            // Store new avatar
            $avatarFile = $request->file('avatar');
            $avatarName = time() . '_' . $user->id . '.' . $avatarFile->getClientOriginalExtension();
            $avatarPath = 'avatars/' . $avatarName;
            
            // Create avatars directory if it doesn't exist
            if (!file_exists(public_path('avatars'))) {
                mkdir(public_path('avatars'), 0755, true);
            }
            
            $avatarFile->move(public_path('avatars'), $avatarName);
            $updateData['avatar'] = $avatarPath;
        }

        $user->update($updateData);

        return response()->json($user);
    }

    public function updateProfile(Request $request, User $user)
    {
        // Ensure user can only update their own profile
        if ($request->user()->id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Não autorizado.'
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'avatar' => 'nullable|file|image|mimes:jpeg,png,jpg,gif|max:5120' // 5MB max
        ]);

        $updateData = [
            'name' => $request->input('name'),
        ];

        // Handle avatar upload if provided
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar && file_exists(public_path($user->avatar))) {
                unlink(public_path($user->avatar));
            }

            // Store new avatar
            $avatarFile = $request->file('avatar');
            $avatarName = time() . '_' . $user->id . '.' . $avatarFile->getClientOriginalExtension();
            $avatarPath = 'avatars/' . $avatarName;
            
            // Create avatars directory if it doesn't exist
            if (!file_exists(public_path('avatars'))) {
                mkdir(public_path('avatars'), 0755, true);
            }
            
            $avatarFile->move(public_path('avatars'), $avatarName);
            $updateData['avatar'] = $avatarPath;
        }

        $user->update($updateData);

        return response()->json($user);
    }

    public function serveAvatar($filename)
    {
        $path = public_path('avatars/' . $filename);
        
        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path);
    }
}