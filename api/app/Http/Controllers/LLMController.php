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
    protected string $defaultProvider;
    protected array $providerConfig;

    public function __construct(RAGService $ragService)
    {
        $this->ragService = $ragService;
        $this->defaultProvider = config('chat.llm.default_provider', 'llm_studio');
        $this->providerConfig = config('chat.providers', []);
    }

    public function generateAnswer($message, Chat $chat)
    {
        $persona = $this->getEffectivePersona($chat);
        
        $answer = $this->generateAnswerWithRAG($message, $chat, $persona);

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

    private function generateAnswerWithRAG(string $userMessage, Chat $chat, $persona = null): string
    {
        Log::info('Generating answer with RAG', [
            'chat_id' => $chat->id,
            'kb_id' => $chat->kb_id,
            'has_persona' => !is_null($persona),
            'provider' => $this->defaultProvider
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

        $personaInstructions = $this->buildPersonaInstructions($persona);
        $conversationHistory = $this->getConversationHistory($chat);

        if (!empty($relevantChunks)) {
            $ragPrompt = $this->ragService->buildRAGPrompt(
                $userMessage, 
                $relevantChunks, 
                $personaInstructions
            );

            Log::info('Built RAG prompt', [
                'prompt'        => $ragPrompt,
                'prompt_length' => strlen($ragPrompt),
                'has_context'   => !empty($relevantChunks),
                'has_history'   => !empty($conversationHistory),
                'chat_id'       => $chat->id
            ]);

            return $this->generateLLMResponse($ragPrompt, $persona, $conversationHistory);
        }

        return $this->generateLLMResponse($userMessage, $persona, $conversationHistory);
    }

    private function generateLLMResponse(string $message, $persona = null, array $conversationHistory = []): string
    {
        $provider = $this->getProviderConfig($this->defaultProvider);
        
        if (!$provider) {
            Log::error("Provider configuration not found: {$this->defaultProvider}");
            return "Erro de configuração do provedor LLM.";
        }

        switch ($provider['type']) {
            case 'openai_compatible':
                return $this->generateOpenAICompatibleResponse($message, $persona, $conversationHistory, $provider);
            
            case 'ollama':
                return $this->generateOllamaResponse($message, $persona, $conversationHistory, $provider);
            
            default:
                Log::error("Unknown provider type: {$provider['type']}");
                return "Tipo de provedor LLM não suportado.";
        }
    }

    private function generateOpenAICompatibleResponse(string $userMessage, $persona, array $conversationHistory, array $providerConfig): string
    {
        try {
            $messages = [];
            
            if ($persona) {
                $systemMessage = $this->buildPersonaInstructions($persona);
                $messages[] = ['role' => 'system', 'content' => $systemMessage];
                
                Log::info("Using persona with creativity level: {$persona->creativity}");
            }
            
            foreach ($conversationHistory as $historyMessage) {
                $messages[] = ['role' => 'user', 'content' => $historyMessage['text']];
                if (!empty($historyMessage['answer'])) {
                    $messages[] = ['role' => 'assistant', 'content' => $historyMessage['answer']];
                }
            }
            
            $messages[] = ['role' => 'user', 'content' => $userMessage];
            
            $temperature = $persona ? $persona->creativity : 0.7;

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout($providerConfig['timeout'] ?? 120)
              ->post($providerConfig['endpoint'], [
                'model' => $providerConfig['model'],
                'messages' => $messages,
                'temperature' => $temperature,
                'max_tokens' => $providerConfig['max_tokens'] ?? 1000,
            ]);

            Log::info('OpenAI Compatible Response', [
                'status' => $response->status(),
                'provider' => $this->defaultProvider
            ]);

            if (!$response->successful()) {
                Log::error('OpenAI Compatible API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'provider' => $this->defaultProvider
                ]);
                return false;
            }

            $responseData = $response->json();

            if (!isset($responseData['choices'][0]['message']['content'])) {
                Log::error('Unexpected OpenAI Compatible response structure', [
                    'response' => $responseData,
                    'provider' => $this->defaultProvider
                ]);
                return false;
            }

            return $responseData['choices'][0]['message']['content'];

        } catch (\Exception $e) {
            Log::error('OpenAI Compatible Exception', [
                'error' => $e->getMessage(),
                'provider' => $this->defaultProvider
            ]);
            return false;
        }
    }

    private function generateOllamaResponse(string $userMessage, $persona, array $conversationHistory, array $providerConfig): string
    {
        try {
            $client = new \GuzzleHttp\Client();
            
            $fullPrompt = $this->buildOllamaPrompt($userMessage, $conversationHistory, $persona);
            
            Log::info('Ollama prompt built', [
                'history_count' => count($conversationHistory),
                'prompt_length' => strlen($fullPrompt)
            ]);

            $response = $client->post($providerConfig['endpoint'], [
                'json' => [
                    'model' => $providerConfig['model'],
                    'prompt' => $fullPrompt,
                    'stream' => false,
                ],
                'timeout' => $providerConfig['timeout'] ?? 100,
            ]);

            $content = $response->getBody()->getContents();
            $data = json_decode($content, true);

            if (!isset($data['response'])) {
                Log::error('Unexpected Ollama response structure', [
                    'response' => $data
                ]);
                return false;
            }

            return $data['response'];

        } catch (\Exception $e) {
            Log::error('Ollama Exception', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function buildOllamaPrompt(string $userMessage, array $conversationHistory, $persona = null): string
    {
        $prompt = "";
        
        if ($persona) {
            $prompt .= "Instruções do sistema: " . $this->buildPersonaInstructions($persona);
            $prompt .= "\n\n";
        }
        
        if (!empty($conversationHistory)) {
            $prompt .= "Histórico da conversa:\n\n";
            foreach ($conversationHistory as $message) {
                $prompt .= "Usuário: " . $message['text'] . "\n";
                if (!empty($message['answer'])) {
                    $prompt .= "Assistente: " . $message['answer'] . "\n\n";
                }
            }
        }
        
        $prompt .= "Usuário: " . $userMessage . "\nAssistente: ";
        
        return $prompt;
    }

    private function buildPersonaInstructions($persona): ?string
    {
        if (!$persona) {
            return null;
        }

        $instructions = $persona->instructions;
        
        if ($persona->response_format) {
            $instructions .= "\n\nFormato de resposta: " . $persona->response_format;
        }
        
        return $instructions;
    }

    private function getProviderConfig(string $provider): ?array
    {
        return $this->providerConfig[$provider] ?? null;
    }

    private function getConversationHistory(Chat $chat): array
    {
        if (!config('chat.context.enabled', true)) {
            return [];
        }
        
        $contextLimit = config('chat.context.limit', 10);
        
        $messages = $chat->messages()
            ->orderBy('created_at', 'desc')
            ->limit($contextLimit)
            ->get(['text', 'answer', 'created_at'])
            ->reverse()
            ->values()
            ->toArray();

        $optimizedMessages = $this->optimizeContextByTokens($messages);

        Log::info('Retrieved conversation history', [
            'chat_id' => $chat->id,
            'messages_count' => count($messages),
            'optimized_count' => count($optimizedMessages),
            'context_limit' => $contextLimit,
            'context_enabled' => config('chat.context.enabled', true)
        ]);

        return $optimizedMessages;
    }

    private function optimizeContextByTokens(array $messages): array
    {
        $maxTokens = config('chat.context.max_tokens', 4000);
        $currentTokens = 0;
        $optimizedMessages = [];
        
        foreach ($messages as $message) {
            $messageTokens = (strlen($message['text']) + strlen($message['answer'] ?? '')) / 4;
            
            if ($currentTokens + $messageTokens > $maxTokens) {
                break;
            }
            
            $optimizedMessages[] = $message;
            $currentTokens += $messageTokens;
        }
        
        return $optimizedMessages;
    }
}