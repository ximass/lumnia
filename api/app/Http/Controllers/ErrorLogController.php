<?php

namespace App\Http\Controllers;

use App\Models\ErrorLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ErrorLogController extends Controller
{
    /**
     * Display a listing of the resource with filters.
     */
    public function index(Request $request)
    {
        $query = ErrorLog::with('user:id,name,email');

        if ($request->has('level') && $request->level) {
            $query->where('level', $request->level);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('created_at', '<=', $request->date_to);
        }

        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('search') && $request->search) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('message', 'ILIKE', $searchTerm)
                  ->orWhere('exception', 'ILIKE', $searchTerm)
                  ->orWhere('file', 'ILIKE', $searchTerm);
            });
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->get('per_page', 50);
        $errorLogs = $query->paginate($perPage);

        return response()->json($errorLogs);
    }

    /**
     * Display the specified resource.
     */
    public function show(ErrorLog $errorLog)
    {
        $errorLog->load('user:id,name,email');
        return response()->json($errorLog);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ErrorLog $errorLog)
    {
        $errorLog->delete();
        return response()->json(['message' => 'Log deletado com sucesso'], 200);
    }

    /**
     * Delete all error logs.
     */
    public function destroyAll()
    {
        ErrorLog::truncate();
        return response()->json(['message' => 'Todos os logs foram deletados com sucesso'], 200);
    }

    /**
     * Get error log statistics.
     */
    public function statistics()
    {
        $stats = [
            'total' => ErrorLog::count(),
            'by_level' => ErrorLog::select('level', DB::raw('count(*) as count'))
                ->groupBy('level')
                ->get()
                ->pluck('count', 'level'),
            'last_24h' => ErrorLog::where('created_at', '>=', now()->subDay())->count(),
            'last_7days' => ErrorLog::where('created_at', '>=', now()->subDays(7))->count(),
            'last_30days' => ErrorLog::where('created_at', '>=', now()->subDays(30))->count(),
        ];

        return response()->json($stats);
    }
}
