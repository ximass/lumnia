<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use App\Models\KnowledgeBase;
use App\Models\Persona;

class LLMController extends Controller
{
    public function generateAnswer($message, $chat = null)
    {
        $knowledgeBase = $chat->knowledgeBase;
        $persona = $this->getEffectivePersona($chat);
        
        //$answer = $this->generateServerAnswerLLMStudio($message, $knowledgeBase, $persona);
        $answer = $this->generateServerAnswerLLMStudio($message, $persona);

        return $answer;
    }

    private function getEffectivePersona($chat = null)
    {
        try {
            if ($chat && $chat->persona_id) {
                $persona = $chat->persona;
                if ($persona && $persona->active) {
                    Log::info("Using chat-specific persona: {$persona->name}");
                    return $persona;
                }
            }

            if ($chat && $chat->user && $chat->user->default_persona_id) {
                $persona = $chat->user->defaultPersona;
                if ($persona && $persona->active) {
                    Log::info("Using user default persona: {$persona->name}");
                    return $persona;
                }
            }

            $defaultPersona = Persona::active()->first();
            if ($defaultPersona) {
                Log::info("Using system default persona: {$defaultPersona->name}");
                return $defaultPersona;
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

    private function generateServerAnswerLLMStudio(string $userMessage, $persona = null): string
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
            ])->timeout(60)->post(env('LLM_API_URL') . '/v1/chat/completions', [
                //'model' => 'gpt-3.5-turbo', // ou outro modelo disponível no seu LLM Studio
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

    private function generateServerAnswerOllama(string $userMessage, KnowledgeBase $knowledgeBase, $stream = false): string
    {
        $client = new \GuzzleHttp\Client();

        $response = $client->post(env('LLM_API_URL') . '/api/generate', [
            'json'    => [
                'model'  => $knowledgeBase->title,
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