<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;

use App\Models\Chat;
use App\Models\Message;
use App\Models\KnowledgeBase;

use App\Http\Controllers\LLMController;
use App\Services\RAGService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    protected RAGService $ragService;

    public function __construct(RAGService $ragService)
    {
        $this->ragService = $ragService;
    }

    public function createChat(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'kb_id' => 'required|exists:knowledge_bases,id',
            'persona_id' => 'nullable|exists:personas,id',
        ]);

        $chat = Chat::create([
            'name' => $request->input('name'),
            'user_id' => $request->user()->id,
            'kb_id' => $request->input('kb_id'),
            'persona_id' => $request->input('persona_id'),
        ]);

        $chat->load('knowledgeBase');

        return response()->json([
            'id' => $chat->id,
            'name' => $chat->name,
            'lastMessage' => '',
            'kb_id' => $chat->kb_id,
            'persona_id' => $chat->persona_id,
            'knowledge_base' => $chat->knowledgeBase,
        ]);
    }

    public function getChats(Request $request)
    {
        $chats = Chat::with(['lastMessage', 'persona', 'user.userPersona', 'knowledgeBase'])
            ->where('user_id', $request->user()->id)
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($chat) {
                return [
                    'id' => $chat->id,
                    'name' => $chat->name,
                    'lastMessage' => $chat->lastMessage ? $chat->lastMessage->text : '',
                    'kb_id' => $chat->kb_id,
                    'persona_id' => $chat->persona_id,
                    'knowledge_base' => $chat->knowledgeBase,
                ];
            });

        return response()->json($chats);
    }

    public function getMessages(Request $request, Chat $chat)
    {
        $messages = $chat->messages()
            ->with([
                'user',
                'rating' => function($query) use ($request) {
                    $query->where('user_id', $request->user()->id);
                }
            ])
            ->orderBy('id')
            ->get();

        return response()->json($messages);
    }

    public function sendMessage(Request $request, Chat $chat)
    {
        $request->validate([
            'text' => 'required|string|max:5000',
        ]);

        if (!$chat->kb_id || !$chat->knowledgeBase) {
            return response()->json([
                'status' => 'error',
                'message' => 'A base de conhecimento associada a este chat não está mais disponível.'
            ], 400);
        }

        $userText = trim($request->input('text'));
        
        if (empty($userText)) {
            return response()->json([
                'status' => 'error',
                'message' => 'A mensagem não pode estar vazia.'
            ], 400);
        }

        $streamingEnabled = config('chat.streaming.enabled', true);

        try {
            $message = Message::create([
                'chat_id' => $chat->id,
                'user_id' => $request->user()->id,
                'text' => $userText,
            ]);

            if ($streamingEnabled) {
                return $this->handleStreamingResponse($chat, $message);
            } else {
                return $this->handleStandardResponse($chat, $message);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in sendMessage: ' . json_encode($e->errors()));

            return response()->json([
                'status' => 'error',
                'message' => 'Dados inválidos.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Exception in sendMessage: ' . $e->getMessage() . ' - Line: ' . $e->getLine() . ' - File: ' . $e->getFile());

            return response()->json([
                'status' => 'error',
                'message' => 'Erro interno do servidor. Tente novamente mais tarde.'
            ], 500);
        }
    }

    private function handleStreamingResponse(Chat $chat, Message $message)
    {
        set_time_limit(0);

        $headers = [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ];

        return response()->stream(function () use ($chat, $message) {
            $this->setupStreamHeaders();
            
            try {
                $this->sendStreamEvent('start', ['message' => 'Iniciando geração de resposta...']);
                
                $result = $this->generateAnswerWithCallback($chat, $message, function ($chunk) {
                    $this->sendStreamEvent('chunk', ['content' => $chunk]);
                    usleep(50000);
                });

                if (!$result) {
                    $this->handleStreamError($message, 'Erro ao gerar resposta');
                } else {
                    $this->sendStreamEvent('complete', [
                        'message_id' => $message->id,
                        'updated_at' => $message->updated_at->toIso8601String()
                    ]);
                }

            } catch (\Exception $e) {
                Log::error('Exception in streaming answer generation: ' . $e->getMessage() . ' - Message ID: ' . $message->id);
                $this->handleStreamError($message, 'Erro interno do servidor');
            }
            
            flush();
            
        }, 200, $headers);
    }

    private function handleStandardResponse(Chat $chat, Message $message)
    {
        set_time_limit(120);

        $answerText = $this->generateAnswer($chat, $message);

        if ($answerText === false || $answerText === null || empty($answerText)) {
            Log::error('LLM failed to generate answer for message ID: ' . $message->id);
            
            $errorMessage = 'Desculpe, ocorreu um erro ao processar sua solicitação. Tente novamente.';
            $message->update(['answer' => $errorMessage]);
            
            return response()->json([
                'status' => 'partial_success',
                'message' => 'Mensagem enviada, mas houve erro na resposta da IA.',
                'message_id' => $message->id,
                'answer' => [
                    'text' => $errorMessage,
                    'updated_at' => $message->created_at->toIso8601String()
                ],
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Mensagem enviada com sucesso!',
            'message_id' => $message->id,
            'answer' => [
                'text' => $answerText,
                'updated_at' => $message->created_at->toIso8601String()
            ],
        ], 200);
    }

    private function setupStreamHeaders()
    {
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
    }

    private function sendStreamEvent(string $type, array $data = [])
    {
        echo "data: " . json_encode(array_merge(['type' => $type], $data)) . "\n\n";
        flush();
    }

    private function handleStreamError(Message $message, string $errorMessage)
    {
        $defaultError = 'Desculpe, ocorreu um erro ao processar sua solicitação. Tente novamente.';
        $message->update(['answer' => $defaultError]);
        
        $this->sendStreamEvent('error', ['message' => $errorMessage]);
    }

    private function generateAnswerWithCallback(Chat $chat, Message $message, callable $callback)
    {
        $llmController = new LLMController($this->ragService);
        
        Log::info('Generating streaming answer for message ID: ' . $message->id . ' in chat ID: ' . $chat->id);
        
        $result = $llmController->generateAnswer($message->text, $chat, true, $callback);

        if (!is_array($result) || !isset($result['answer']) || empty($result['answer'])) {
            Log::error('LLM failed to generate streaming answer for message ID: ' . $message->id);
            return false;
        }

        $answerText = $result['answer'];
        $chunks = $result['chunks'] ?? [];
        
        $message->update(['answer' => $answerText]);
        
        $this->saveInformationSources($message, $chunks);
        
        return true;
    }

    private function generateAnswer(Chat $chat, Message $message)
    {
        try {
            $llmController = new LLMController($this->ragService);

            Log::info('Generating answer for message ID: ' . $message->id . ' in chat ID: ' . $chat->id);

            $result = $llmController->generateAnswer($message->text, $chat, false);

            if (!is_array($result) || !isset($result['answer']) || empty(trim($result['answer']))) {
                Log::error('LLM Controller returned invalid response for message ID: ' . $message->id);
                return false;
            }

            $answerText = $result['answer'];
            $chunks = $result['chunks'] ?? [];

            $message->update(['answer' => $answerText]);

            $this->saveInformationSources($message, $chunks);

            Log::info('Answer generated successfully for message ID: ' . $message->id);

            return $answerText;

        } catch (\Exception $e) {
            Log::error('Exception in generateAnswer: ' . $e->getMessage() . ' - Message ID: ' . $message->id);
            return false;
        }
    }

    public function deleteChat(Request $request, Chat $chat)
    {
        $chat->delete();

        return response()->json(['status' => 'Chat deleted!']);
    }

    public function updateChat(Request $request, Chat $chat)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'kb_id' => 'sometimes|nullable|exists:knowledge_bases,id',
            'persona_id' => 'sometimes|nullable|exists:personas,id',
        ]);

        $updateData = [];
        
        if ($request->has('name')) {
            $updateData['name'] = $request->input('name');
        }
        
        if ($request->has('kb_id')) {
            $updateData['kb_id'] = $request->input('kb_id');
        }
        
        if ($request->has('persona_id')) {
            $updateData['persona_id'] = $request->input('persona_id');
        }

        $chat->update($updateData);

        return response()->json($chat);
    }

    private function saveInformationSources(Message $message, array $chunks)
    {
        if (empty($chunks)) {
            return;
        }

        foreach ($chunks as $chunk) {
            $message->informationSources()->create([
                'content' => $chunk->text
            ]);
        }
        
        Log::info('Saved ' . count($chunks) . ' information sources for message ID: ' . $message->id);
    }

    public function clearContext(Request $request, Chat $chat)
    {
        try {
            if ($chat->user_id !== $request->user()->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Você não tem permissão para limpar o contexto deste chat.'
                ], 403);
            }

            $messageCount = $chat->messages()->count();
            $chat->messages()->delete();

            Log::info('Chat context cleared', [
                'chat_id' => $chat->id,
                'messages_deleted' => $messageCount,
                'user_id' => $request->user()->id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Contexto do chat limpo com sucesso!',
                'data' => [
                    'messages_deleted' => $messageCount
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Exception in clearContext: ' . $e->getMessage() . ' - Chat ID: ' . $chat->id);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro interno do servidor. Tente novamente mais tarde.'
            ], 500);
        }
    }

    public function getContextInfo(Request $request, Chat $chat)
    {
        try {
            if ($chat->user_id !== $request->user()->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Você não tem permissão para acessar informações deste chat.'
                ], 403);
            }

            $contextLimit = config('chat.context.limit', 10);
            $contextEnabled = config('chat.context.enabled', true);
            $maxTokens = config('chat.context.max_tokens', 4000);
            
            $totalMessages = $chat->messages()->count();
            $contextMessages = min($totalMessages, $contextLimit);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'context_enabled' => $contextEnabled,
                    'context_limit' => $contextLimit,
                    'max_tokens' => $maxTokens,
                    'total_messages' => $totalMessages,
                    'context_messages' => $contextEnabled ? $contextMessages : 0,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Exception in getContextInfo: ' . $e->getMessage() . ' - Chat ID: ' . $chat->id);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro interno do servidor. Tente novamente mais tarde.'
            ], 500);
        }
    }
}
