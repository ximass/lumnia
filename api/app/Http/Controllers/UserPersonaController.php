<?php

namespace App\Http\Controllers;

use App\Models\UserPersona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UserPersonaController extends Controller
{
    public function show(Request $request)
    {
        try {
            $userPersona = UserPersona::where('user_id', $request->user()->id)->first();
            
            if (!$userPersona) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Persona de usuário não encontrada.'
                ], 404);
            }

            return response()->json($userPersona);
        } catch (\Exception $e) {
            Log::error('Exception in UserPersonaController@show: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar persona do usuário.'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'instructions' => 'required|string|max:10000',
                'response_format' => 'nullable|string|max:2000',
                'creativity' => 'nullable|numeric|between:0,1'
            ]);

            $existingUserPersona = UserPersona::where('user_id', $request->user()->id)->first();
            
            if ($existingUserPersona) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Usuário já possui uma persona configurada.'
                ], 400);
            }

            $validated['user_id'] = $request->user()->id;
            $validated['creativity'] = $validated['creativity'] ?? 0.5;

            $userPersona = UserPersona::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Persona de usuário criada com sucesso!',
                'data' => $userPersona
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados inválidos.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Exception in UserPersonaController@store: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Erro interno do servidor.'
            ], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'instructions' => 'required|string|max:10000',
                'response_format' => 'nullable|string|max:2000',
                'creativity' => 'nullable|numeric|between:0,1'
            ]);

            $userPersona = UserPersona::where('user_id', $request->user()->id)->first();

            if (!$userPersona) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Persona de usuário não encontrada.'
                ], 404);
            }

            $userPersona->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Persona de usuário atualizada com sucesso!',
                'data' => $userPersona
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados inválidos.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Exception in UserPersonaController@update: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Erro interno do servidor.'
            ], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $userPersona = UserPersona::where('user_id', $request->user()->id)->first();

            if (!$userPersona) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Persona de usuário não encontrada.'
                ], 404);
            }

            $userPersona->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Persona de usuário excluída com sucesso!'
            ]);

        } catch (\Exception $e) {
            Log::error('Exception in UserPersonaController@destroy: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Erro interno do servidor.'
            ], 500);
        }
    }
}
