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
        $this->defaultProvider = config('providers.default_llm', 'lm_studio');
        $this->providerConfig = config('providers.llm', []);
    }

    public function generateAnswer($message, Chat $chat)
    {
        $persona = $this->getEffectivePersona($chat);
        
        $result = $this->generateAnswerWithRAG($message, $chat, $persona);

        return $result;
    }

    public function generateAnswerStream($message, Chat $chat, $callback = null)
    {
        $persona = $this->getEffectivePersona($chat);
        
        $result = $this->generateAnswerWithRAGStream($message, $chat, $persona, $callback);
        
        return $result;
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

    private function generateAnswerWithRAG(string $userMessage, Chat $chat, $persona = null): array
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

            $answer = $this->generateLLMResponse($ragPrompt, $persona, $conversationHistory);
            
            return [
                'answer' => $answer,
                'chunks' => $relevantChunks
            ];
        }

        $answer = $this->generateLLMResponse($userMessage, $persona, $conversationHistory);
        
        return [
            'answer' => $answer,
            'chunks' => []
        ];
    }

    private function generateAnswerWithRAGStream(string $userMessage, Chat $chat, $persona = null, $callback = null)
    {
        Log::info('Generating answer with RAG Stream', [
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

        Log::info('Retrieved chunks for RAG Stream', [
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

            Log::info('Built RAG prompt for stream', [
                'prompt_length' => strlen($ragPrompt),
                'has_context' => !empty($relevantChunks),
                'has_history' => !empty($conversationHistory),
                'chat_id' => $chat->id
            ]);

            $answer = $this->generateLLMResponseStream($ragPrompt, $persona, $conversationHistory, $callback);
            
            return [
                'answer' => $answer,
                'chunks' => $relevantChunks
            ];
        }

        $answer = $this->generateLLMResponseStream($userMessage, $persona, $conversationHistory, $callback);
        
        return [
            'answer' => $answer,
            'chunks' => []
        ];
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

    private function generateLLMResponseStream(string $message, $persona = null, array $conversationHistory = [], $callback = null)
    {
        $provider = $this->getProviderConfig($this->defaultProvider);
        
        if (!$provider) {
            Log::error("Provider configuration not found: {$this->defaultProvider}");
            return "Erro de configuração do provedor LLM.";
        }

        switch ($provider['type']) {
            case 'openai_compatible':
                return $this->generateOpenAICompatibleResponseStream($message, $persona, $conversationHistory, $provider, $callback);
            
            case 'ollama':
                return $this->generateOllamaResponseStream($message, $persona, $conversationHistory, $provider, $callback);
            
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

    private function generateOpenAICompatibleResponseStream(string $userMessage, $persona, array $conversationHistory, array $providerConfig, $callback = null): string
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

            $client = new \GuzzleHttp\Client();
            $fullResponse = '';

            $response = $client->post($providerConfig['endpoint'], [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $providerConfig['model'],
                    'messages' => $messages,
                    'temperature' => $temperature,
                    'max_tokens' => $providerConfig['max_tokens'] ?? 1000,
                    'stream' => true,
                ],
                'timeout' => $providerConfig['timeout'] ?? 120,
                'stream' => true,
            ]);

            $body = $response->getBody();
            $buffer = '';
            
            while (!$body->eof()) {
                $chunk = $body->read(1);
                $buffer .= $chunk;
                
                // Process complete lines
                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 1);
                    
                    $line = trim($line);
                    if (strpos($line, 'data: ') === 0) {
                        $data = trim(substr($line, 6));
                        
                        if ($data === '[DONE]') {
                            return $fullResponse;
                        }
                        
                        if (!empty($data) && $data !== '') {
                            $json = json_decode($data, true);
                            if ($json && isset($json['choices'][0]['delta']['content'])) {
                                $content = $json['choices'][0]['delta']['content'];
                                $fullResponse .= $content;
                                
                                Log::info('LLM chunk received', ['content_length' => strlen($content), 'content_preview' => substr($content, 0, 30)]);
                                
                                if ($callback) {
                                    $callback($content);
                                }
                            }
                        }
                    }
                }
            }

            Log::info('OpenAI Compatible Stream Response completed', [
                'provider' => $this->defaultProvider,
                'response_length' => strlen($fullResponse)
            ]);

            return $fullResponse;

        } catch (\Exception $e) {
            Log::error('OpenAI Compatible Stream Exception', [
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

    private function generateOllamaResponseStream(string $userMessage, $persona, array $conversationHistory, array $providerConfig, $callback = null): string
    {
        try {
            $client = new \GuzzleHttp\Client();
            
            $fullPrompt = $this->buildOllamaPrompt($userMessage, $conversationHistory, $persona);
            
            Log::info('Ollama stream prompt built', [
                'history_count' => count($conversationHistory),
                'prompt_length' => strlen($fullPrompt)
            ]);

            $fullResponse = '';

            $response = $client->post($providerConfig['endpoint'], [
                'json' => [
                    'model' => $providerConfig['model'],
                    'prompt' => $fullPrompt,
                    'stream' => true,
                ],
                'timeout' => $providerConfig['timeout'] ?? 100,
                'stream' => true,
            ]);

            $body = $response->getBody();
            $buffer = '';
            
            while (!$body->eof()) {
                $chunk = $body->read(1);
                $buffer .= $chunk;
                
                // Process complete lines
                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 1);
                    
                    if (trim($line) !== '') {
                        $data = json_decode($line, true);
                        if ($data && isset($data['response'])) {
                            $content = $data['response'];
                            $fullResponse .= $content;
                            
                            if ($callback) {
                                $callback($content);
                            }
                            
                            if (isset($data['done']) && $data['done']) {
                                return $fullResponse;
                            }
                        }
                    }
                }
            }

            Log::info('Ollama Stream Response completed', [
                'response_length' => strlen($fullResponse)
            ]);

            return $fullResponse;

        } catch (\Exception $e) {
            Log::error('Ollama Stream Exception', [
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
        $config = $this->providerConfig[$provider] ?? null;
        
        if ($config && isset($config['base_url']) && isset($config['chat_endpoint'])) {
            $config['endpoint'] = rtrim($config['base_url'], '/') . $config['chat_endpoint'];
        }
        
        return $config;
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