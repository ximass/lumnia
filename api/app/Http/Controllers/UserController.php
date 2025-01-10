<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function search(Request $request)
    {
        $request->validate([
            'search' => 'required|string'
        ]);

        $search = $request->query('search', '');
        
        $users = User::where('name', 'ILIKE', "%{$search}%")
                     ->select('id', 'name')
                     ->limit(10)
                     ->get();

        return response()->json($users);
    }
}