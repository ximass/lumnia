<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MessageRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MessageRatingController extends Controller
{
    public function store(Request $request, Message $message)
    {
        try {
            $request->validate([
                'rating' => 'required|in:like,dislike',
            ]);

            $existingRating = MessageRating::where('message_id', $message->id)
                ->where('user_id', $request->user()->id)
                ->first();

            if ($existingRating) {
                $existingRating->update([
                    'rating' => $request->rating
                ]);
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Avaliação atualizada com sucesso!',
                    'data' => $existingRating
                ]);
            } else {
                $rating = MessageRating::create([
                    'message_id' => $message->id,
                    'user_id' => $request->user()->id,
                    'rating' => $request->rating
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Avaliação criada com sucesso!',
                    'data' => $rating
                ]);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados inválidos.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Exception in MessageRatingController::store: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Erro interno do servidor. Tente novamente mais tarde.'
            ], 500);
        }
    }

    public function destroy(Request $request, Message $message)
    {
        try {
            $rating = MessageRating::where('message_id', $message->id)
                ->where('user_id', $request->user()->id)
                ->first();

            if (!$rating) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Avaliação não encontrada.'
                ], 404);
            }

            $rating->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Avaliação removida com sucesso!'
            ]);

        } catch (\Exception $e) {
            Log::error('Exception in MessageRatingController::destroy: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Erro interno do servidor. Tente novamente mais tarde.'
            ], 500);
        }
    }

    /**
     * List message ratings with filters and pagination.
     */
    public function index(Request $request)
    {
        try {
            $query = MessageRating::with(['user:id,name,email', 'message:id,chat_id,user_id,text,answer']);

            if ($request->has('rating') && $request->rating) {
                $query->where('rating', $request->rating);
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
                $query->whereHas('message', function ($q) use ($searchTerm) {
                    $q->where('text', 'ILIKE', $searchTerm)
                      ->orWhere('answer', 'ILIKE', $searchTerm);
                })->orWhereHas('user', function ($q) use ($searchTerm) {
                    $q->where('name', 'ILIKE', $searchTerm);
                });
            }

            $query->orderBy('created_at', 'desc');

            $perPage = $request->get('per_page', 50);
            $ratings = $query->paginate($perPage);

            return response()->json($ratings);
        } catch (\Exception $e) {
            Log::error('Exception in MessageRatingController::index: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Erro interno do servidor. Tente novamente mais tarde.'
            ], 500);
        }
    }

    public function show(Request $request, Message $message)
    {
        try {
            $rating = MessageRating::where('message_id', $message->id)
                ->where('user_id', $request->user()->id)
                ->first();

            return response()->json([
                'status' => 'success',
                'data' => $rating
            ]);

        } catch (\Exception $e) {
            Log::error('Exception in MessageRatingController::show: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Erro interno do servidor. Tente novamente mais tarde.'
            ], 500);
        }
    }
}
