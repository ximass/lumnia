<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function createChat(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'knowledge_base_ids' => 'array',
            'knowledge_base_ids.*' => 'exists:knowledge_bases,id',
        ]);

        $chat = Chat::create([
            'name' => $request->input('name'),
            'user_id' => $request->user()->id,
        ]);

        $knowledgeBaseIds = $request->input('knowledge_base_ids', []);
        $chat->knowledgeBases()->sync($knowledgeBaseIds);

        return response()->json($chat);
    }

    public function getChats(Request $request)
    {
        $chats = Chat::with('lastMessage')
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
        $messages = $chat->messages()->with('user')->get();

        return response()->json($messages);
    }

    public function sendMessage(Request $request, Chat $chat)
    {
        $message = Message::create([
            'chat_id' => $chat->id,
            'user_id' => $request->user()->id,
            'text' => $request->input('text'),
        ]);

        broadcast(new MessageSent($chat->id, $message->text, $request->user()));

        $answerText = $this->generateServerAnswerOllama($message->text);

        $message->update(['answer' => $answerText]);

        return response()->json([
            'status' => 'Message sent!',
            'answer' => [
                'text' => $answerText,
                'updated_at' => $message->created_at->toIso8601String()
            ],
        ]);
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
            'knowledge_base_ids' => 'array',
            'knowledge_base_ids.*' => 'exists:knowledge_bases,id',
        ]);

        $chat->update([
            'name' => $request->input('name'),
        ]);

        $knowledgeBaseIds = $request->input('knowledge_base_ids', []);
        $chat->knowledgeBases()->sync($knowledgeBaseIds);

        return response()->json($chat);
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
        $response = Http::post(env('LLM_API_URL') . '/v1/chat/completions', [
            'messages' => [
                ['role' => 'user', 'content' => $userMessage]
            ],
            'temperature' => 0.7,
            'max_tokens' => 1000,
        ]);

        return $response->json()['choices'][0]['message']['content'];
    }

    private function generateServerAnswerOllama(string $userMessage, $stream = false): string
    {
        $client = new \GuzzleHttp\Client();

        $response = $client->post(env('LLM_API_URL') . '/api/generate', [
            'json'    => [
                'model'  => 'deepseek-r1',
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