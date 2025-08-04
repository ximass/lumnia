<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use App\Models\KnowledgeBase;

class LLMController extends Controller
{
    public function generateAnswer($message, $knowledgeBase)
    {
        //$answer = $this->generateServerAnswerLLMStudio($message, $knowledgeBase);
        $answer = $this->generateServerAnswerLLMStudio($message);

        return $answer;
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

    private function generateServerAnswerLLMStudio(string $userMessage): string
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(60)->post(env('LLM_API_URL') . '/v1/chat/completions', [
                //'model' => 'gpt-3.5-turbo', // ou outro modelo disponível no seu LLM Studio
                'messages' => [
                    ['role' => 'user', 'content' => $userMessage]
                ],
                'temperature' => 0.7,
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