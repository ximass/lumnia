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
}