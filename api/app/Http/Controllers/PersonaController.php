<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PersonaController extends Controller
{
    public function index()
    {
        try {
            $personas = Persona::orderBy('name')->get();
            return response()->json($personas);
        } catch (\Exception $e) {
            Log::error('Exception in PersonaController@index: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar personas.'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string|max:1000',
                'instructions' => 'required|string|max:10000',
                'response_format' => 'nullable|string|max:2000',
                'keywords' => 'nullable|array',
                'keywords.*' => 'string|max:100',
                'creativity' => 'nullable|numeric|between:0,1',
                'active' => 'boolean'
            ]);

            // Converter keywords para array se vier como string
            if (isset($validated['keywords']) && is_string($validated['keywords'])) {
                $validated['keywords'] = array_map('trim', explode(',', $validated['keywords']));
            }

            $persona = Persona::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Persona criada com sucesso!',
                'data' => $persona
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados inválidos.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Exception in PersonaController@store: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Erro interno do servidor.'
            ], 500);
        }
    }

    public function show(Persona $persona)
    {
        try {
            return response()->json($persona);
        } catch (\Exception $e) {
            Log::error('Exception in PersonaController@show: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar persona.'
            ], 500);
        }
    }

    public function update(Request $request, Persona $persona)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string|max:1000',
                'instructions' => 'required|string|max:10000',
                'response_format' => 'nullable|string|max:2000',
                'keywords' => 'nullable|array',
                'keywords.*' => 'string|max:100',
                'creativity' => 'nullable|numeric|between:0,1',
                'active' => 'boolean'
            ]);

            // Converter keywords para array se vier como string
            if (isset($validated['keywords']) && is_string($validated['keywords'])) {
                $validated['keywords'] = array_map('trim', explode(',', $validated['keywords']));
            }

            $persona->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Persona atualizada com sucesso!',
                'data' => $persona
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados inválidos.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Exception in PersonaController@update: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Erro interno do servidor.'
            ], 500);
        }
    }

    public function destroy(Persona $persona)
    {
        try {
            // Verificar se a persona está sendo usada em chats
            $chatsCount = $persona->chats()->count();
            
            if ($chatsCount > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Não é possível excluir esta persona pois ela está sendo usada em {$chatsCount} chat(s)."
                ], 400);
            }

            $persona->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Persona excluída com sucesso!'
            ]);

        } catch (\Exception $e) {
            Log::error('Exception in PersonaController@destroy: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Erro interno do servidor.'
            ], 500);
        }
    }

    public function getActivePersonas()
    {
        try {
            $personas = Persona::active()
                ->select('id', 'name', 'description')
                ->orderBy('name')
                ->get();
                
            return response()->json($personas);
        } catch (\Exception $e) {
            Log::error('Exception in PersonaController@getActivePersonas: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar personas ativas.'
            ], 500);
        }
    }
}
