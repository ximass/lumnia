<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

use App\Models\Message;

class MessageController extends Controller
{
    public function getInformationSources(Request $request, Message $message): JsonResponse
    {
        try {
            $informationSources = $message->informationSources()->get();

            return response()->json($informationSources);
            
        } catch (\Exception $e) {
            Log::error('Error retrieving information sources', [
                'message_id' => $message->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar fontes de informação.'
            ], 500);
        }
    }

    /**
     * Return paginated messages that have at least one information source attached.
     * Supports query params: page, per_page
     */
    public function withInformationSources(Request $request): JsonResponse
    {
        try {
            $perPage = intval($request->query('per_page', 25));
            $page = intval($request->query('page', 1));

            $query = Message::whereHas('informationSources');

            $paginator = $query->with('informationSources', 'user')->paginate($perPage, ['*'], 'page', $page);

            return response()->json($paginator);
        } catch (\Exception $e) {
            Log::error('Error retrieving messages with information sources', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Erro ao buscar mensagens com fontes.'], 500);
        }
    }

    /**
     * Return stats: total messages and number of messages that have information sources.
     */
    public function withInformationSourcesStats(Request $request): JsonResponse
    {
        try {
            $total = Message::count();
            $withSources = Message::whereHas('informationSources')->count();

            return response()->json([ 'total' => $total, 'with_sources' => $withSources ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving messages with sources stats', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Erro ao buscar estatísticas.'], 500);
        }
    }
}