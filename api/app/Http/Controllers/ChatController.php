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
            'kb_id' => 'nullable|exists:knowledge_bases,id',
            'persona_id' => 'nullable|exists:personas,id',
        ]);

        $chat = Chat::create([
            'name' => $request->input('name'),
            'user_id' => $request->user()->id,
            'kb_id' => $request->input('kb_id'),
            'persona_id' => $request->input('persona_id'),
        ]);

        return response()->json($chat);
    }

    public function getChats(Request $request)
    {
        $chats = Chat::with(['lastMessage', 'persona', 'user.userPersona'])
            ->where('user_id', $request->user()->id)
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($chat) {
                return [
                    'id' => $chat->id,
                    'name' => $chat->name,
                    'lastMessage' => $chat->lastMessage ? $chat->lastMessage->text : '',
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
            ->get();

        return response()->json($messages);
    }

    public function sendMessage(Request $request, Chat $chat)
    {
        $streamingEnabled = config('chat.streaming.enabled', true);

        $request->validate([
            'text' => 'required|string|max:5000',
        ]);

        $userText = trim($request->input('text'));
        
        if (empty($userText)) {
            return response()->json([
                'status' => 'error',
                'message' => 'A mensagem não pode estar vazia.'
            ], 400);
        }
        
        if ($streamingEnabled) {
            set_time_limit(0);

            try {
                $message = Message::create([
                    'chat_id' => $chat->id,
                    'user_id' => $request->user()->id,
                    'text' => $userText,
                ]);

                $headers = [
                    'Content-Type' => 'text/event-stream',
                    'Cache-Control' => 'no-cache',
                    'Connection' => 'keep-alive',
                    'X-Accel-Buffering' => 'no', // Disable nginx buffering
                ];

                return response()->stream(function () use ($chat, $message) {
                    if (ob_get_level()) {
                        ob_end_clean();
                    }
                    
                    // Set content type again for safety
                    header('Content-Type: text/event-stream');
                    header('Cache-Control: no-cache');
                    header('Connection: keep-alive');
                    header('X-Accel-Buffering: no');
                    
                    $fullAnswer = '';
                    $chunks = [];
                    
                    try {
                        $llmController = new LLMController($this->ragService);
                        
                        Log::info('Generating streaming answer for message ID: ' . $message->id . ' in chat ID: ' . $chat->id);
                        
                        // Send initial message to establish connection
                        echo "data: " . json_encode([
                            'type' => 'start',
                            'message' => 'Iniciando geração de resposta...'
                        ]) . "\n\n";
                        flush();
                        
                        $result = $llmController->generateAnswerStream($message->text, $chat, function ($chunk) use (&$fullAnswer) {
                            $fullAnswer .= $chunk;
                            
                            //Log::info('Streaming chunk sent', ['chunk_length' => strlen($chunk), 'chunk' => substr($chunk, 0, 50)]);
                            
                            echo "data: " . json_encode([
                                'type' => 'chunk',
                                'content' => $chunk
                            ]) . "\n\n";
                            
                            // Force immediate output
                            flush();
                            
                            // Small delay to ensure chunks are sent separately
                            usleep(50000); // 50ms delay
                        });

                        if (!is_array($result) || !isset($result['answer']) || empty($result['answer'])) {
                            Log::error('LLM failed to generate streaming answer for message ID: ' . $message->id);
                            
                            $errorAnswer = 'Desculpe, ocorreu um erro ao processar sua solicitação. Tente novamente.';
                            $message->update(['answer' => $errorAnswer]);
                            
                            echo "data: " . json_encode([
                                'type' => 'error',
                                'message' => 'Erro ao gerar resposta'
                            ]) . "\n\n";
                        } else {
                            $answerText = $result['answer'];
                            $chunks = $result['chunks'] ?? [];
                            
                            $message->update(['answer' => $answerText]);
                            
                            // Salvar os chunks utilizados como fontes de informação
                            if (!empty($chunks)) {
                                foreach ($chunks as $chunk) {
                                    $message->informationSources()->create([
                                        'content' => $chunk->text
                                    ]);
                                }
                                
                                Log::info('Saved ' . count($chunks) . ' information sources for streaming message ID: ' . $message->id);
                            }
                            
                            echo "data: " . json_encode([
                                'type' => 'complete',
                                'message_id' => $message->id,
                                'updated_at' => $message->updated_at->toIso8601String()
                            ]) . "\n\n";
                        }

                    } catch (\Exception $e) {
                        Log::error('Exception in streaming answer generation: ' . $e->getMessage() . ' - Message ID: ' . $message->id);
                        
                        $errorAnswer = 'Desculpe, ocorreu um erro ao processar sua solicitação. Tente novamente.';
                        $message->update(['answer' => $errorAnswer]);
                        
                        echo "data: " . json_encode([
                            'type' => 'error',
                            'message' => 'Erro interno do servidor'
                        ]) . "\n\n";
                    }
                    
                    flush();
                    
                }, 200, $headers);

            } catch (\Illuminate\Validation\ValidationException $e) {
                Log::error('Validation error in sendMessage (streaming): ' . json_encode($e->errors()));

                return response()->json([
                    'status' => 'error',
                    'message' => 'Dados inválidos.',
                    'errors' => $e->errors()
                ], 422);

            } catch (\Exception $e) {
                Log::error('Exception in sendMessage (streaming): ' . $e->getMessage() . ' - Line: ' . $e->getLine() . ' - File: ' . $e->getFile());

                return response()->json([
                    'status' => 'error',
                    'message' => 'Erro interno do servidor. Tente novamente mais tarde.'
                ], 500);
            }
        }

        set_time_limit(120);

        try {
            $message = Message::create([
                'chat_id' => $chat->id,
                'user_id' => $request->user()->id,
                'text' => $userText,
            ]);

            $answerText = $this->generateAnswer($chat, $message);

            if ($answerText === false || $answerText === null || empty($answerText)) {
                Log::error('LLM failed to generate answer for message ID: ' . $message->id);
                
                $message->update(['answer' => 'Desculpe, ocorreu um erro ao processar sua solicitação. Tente novamente.']);
                
                return response()->json([
                    'status' => 'partial_success',
                    'message' => 'Mensagem enviada, mas houve erro na resposta da IA.',
                    'message_id' => $message->id,
                    'answer' => [
                        'text' => 'Desculpe, ocorreu um erro ao processar sua solicitação. Tente novamente.',
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

    public function deleteChat(Request $request, Chat $chat)
    {
        $chat->delete();

        return response()->json(['status' => 'Chat deleted!']);
    }

    public function updateChat(Request $request, Chat $chat)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'kb_id' => 'nullable|exists:knowledge_bases,id',
            'persona_id' => 'nullable|exists:personas,id',
        ]);

        $chat->update([
            'name'             => $request->input('name'),
            'kb_id'            => $request->input('kb_id'),
            'persona_id'       => $request->input('persona_id'),
        ]);

        return response()->json($chat);
    }

    private function generateAnswer($chat, $message)
    {
        try {
            $llmController = new LLMController($this->ragService);

            Log::info('Generating answer for message ID: ' . $message->id . ' in chat ID: ' . $chat->id);

            $result = $llmController->generateAnswer($message->text, $chat);

            if (!is_array($result) || !isset($result['answer']) || empty(trim($result['answer']))) {
                Log::error('LLM Controller returned invalid response for message ID: ' . $message->id);
                return false;
            }

            $answerText = $result['answer'];
            $chunks = $result['chunks'] ?? [];

            $message->update(['answer' => $answerText]);

            if (!empty($chunks)) {
                foreach ($chunks as $chunk) {
                    $message->informationSources()->create([
                        'content' => $chunk->text
                    ]);
                }
            }

            Log::info('Answer generated successfully for message ID: ' . $message->id);

            return $answerText;

        } catch (\Exception $e) {
            Log::error('Exception in generateAnswer: ' . $e->getMessage() . ' - Message ID: ' . $message->id);
            return false;
        }
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
