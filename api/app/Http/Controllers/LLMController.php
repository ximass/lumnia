<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Services\RAGService;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use App\Models\KnowledgeBase;
use App\Models\Persona;

class LLMController extends Controller
{
    protected RAGService $ragService;

    public function __construct(RAGService $ragService)
    {
        $this->ragService = $ragService;
    }

    public function generateAnswer($message, Chat $chat)
    {
        $persona = $this->getEffectivePersona($chat);
        
        $answer = $this->generateServerAnswerWithRAG($message, $chat, $persona);

        return $answer;
    }

    private function getEffectivePersona(Chat $chat)
    {
        try {
            if ($chat && $chat->persona_id) {
                $persona = $chat->persona;
                if ($persona && $persona->active) {
                    Log::info("Using chat-specific persona: {$persona->name}");
                    return $persona;
                }
            }

            if ($chat && $chat->user && $chat->user->userPersona) {
                $persona = $chat->user->userPersona;
                if ($persona && $persona->active) {
                    Log::info("Using user default persona: {$persona->name}");
                    return $persona;
                }
            }

            Log::warning("No active persona found, using null");
            return null;

        } catch (\Exception $e) {
            Log::error("Error getting effective persona: " . $e->getMessage());
            return null;
        }
    }

    private function generateServerAnswerScript(string $userMessage): string
    {
        $command = escapeshellcmd('python C:\projetos\lumnia\scripts\test3.py ' . escapeshellarg($userMessage));
        $output  = shell_exec($command);

        Log::info($output);

        if ($output === null) {
            Log::error("Failed to execute the Python script.");
            return "An error occurred while processing your request.";
        }

        $decodedOutput = json_decode($output);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("JSON decode error: " . json_last_error_msg());
            return "An error occurred while processing your request.";
        }

        if (!isset($decodedOutput->choices[0]->message)) {
            Log::error("Unexpected response structure: " . $output);
            return "An error occurred while processing your request.";
        }

        return $decodedOutput->choices[0]->message;
    }

    private function generateServerAnswerLLMStudio(string $userMessage, $persona = null, bool $isRAG = false): string
    {
        try {
            $messages = [];
            
            if ($persona) {
                $systemMessage = $persona->instructions;
                
                if ($persona->response_format) {
                    $systemMessage .= "\n\nFormato de resposta: " . $persona->response_format;
                }
                
                $messages[] = ['role' => 'system', 'content' => $systemMessage];
                
                Log::info("Using persona '{$persona->name}' with creativity level: {$persona->creativity}");
            }
            
            $messages[] = ['role' => 'user', 'content' => $userMessage];
            
            $temperature = $persona ? $persona->creativity : 0.7;

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(120)->post(env('LLM_API_URL') . '/v1/chat/completions', [
                'model' => 'gemma-3-1b-it-qat',
                'messages' => $messages,
                'temperature' => $temperature,
                'max_tokens' => 1000,
            ]);

            Log::info('LLM Studio Response Status: ' . $response->status());
            Log::info('LLM Studio Response Body: ' . $response->body());

            if (!$response->successful()) {
                Log::error('LLM Studio API Error - Status: ' . $response->status() . ', Body: ' . $response->body());
                return false;
            }

            $responseData = $response->json();

            if (!isset($responseData['choices'][0]['message']['content'])) {
                Log::error('Unexpected LLM Studio response structure: ' . json_encode($responseData));
                return false;
            }

            return $responseData['choices'][0]['message']['content'];

        } catch (\Exception $e) {
            Log::error('LLM Studio Exception: ' . $e->getMessage());
            return false;
        }
    }

    private function generateServerAnswerWithRAG(string $userMessage, Chat $chat, $persona = null): string
    {
        try {
            Log::info('Generating answer with RAG', [
                'chat_id' => $chat->id,
                'kb_id' => $chat->kb_id,
                'has_persona' => !is_null($persona)
            ]);

            $relevantChunks = $this->ragService->retrieveRelevantChunks(
                $userMessage, 
                $chat->kb_id, 
                5, // topK chunks
                0.3 // threshold
            );

            Log::info('Retrieved chunks for RAG', [
                'chunks_count' => count($relevantChunks),
                'chat_id' => $chat->id
            ]);

            $personaInstructions = null;
            if ($persona) {
                $personaInstructions = $persona->instructions;
                if ($persona->response_format) {
                    $personaInstructions .= "\n\nFormato de resposta: " . $persona->response_format;
                }
            }

            $ragPrompt = $this->ragService->buildRAGPrompt(
                $userMessage, 
                $relevantChunks, 
                $personaInstructions
            );

            Log::info('Built RAG prompt', [
                'prompt' => $ragPrompt,
                'prompt_length' => strlen($ragPrompt),
                'has_context' => !empty($relevantChunks),
                'chat_id' => $chat->id
            ]);

            return $this->generateServerAnswerLLMStudio($ragPrompt, $persona, true);

        } catch (\Exception $e) {
            Log::error('RAG generation failed, falling back to basic LLM', [
                'error' => $e->getMessage(),
                'chat_id' => $chat->id
            ]);
            
            return $this->generateServerAnswerLLMStudio($userMessage, $persona);
        }
    }

    private function generateServerAnswerOllama(string $userMessage, $stream = false): string
    {
        $client = new \GuzzleHttp\Client();

        $response = $client->post(env('LLM_API_URL') . '/api/generate', [
            'json'    => [
                'model'  => env('LLM_DEFAULT_MODEL', 'llama2'),
                'prompt' => $userMessage,
                'stream' => $stream,
            ],
            'timeout' => 100,
            'stream'  => $stream,
        ]);

        $content = $response->getBody()->getContents();
        $answer = json_decode($content, true)['response'];

        return $answer;
    }
}