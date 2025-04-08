<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Chat;
use App\Models\Message;

use LLMController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function createChat(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'knowledge_base_id' => 'nullable|exists:knowledge_bases,id',
        ]);

        $chat = Chat::create([
            'name' => $request->input('name'),
            'user_id' => $request->user()->id,
            'knowledge_base_id'=> $request->input('knowledge_base_id'),
        ]);

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

        $answerText = $this->generateAnswer($chat, $message->text);

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
            'knowledge_base_id' => 'nullable|exists:knowledge_bases,id',
        ]);

        $chat->update([
            'name'             => $request->input('name'),
            'knowledge_base_id'=> $request->input('knowledge_base_id'),
        ]);

        return response()->json($chat);
    }

    private function generateAnswer($chat, $message)
    {
        $knowledgeBase = $chat->knowledgeBase();

        $answerText = LLMController::generateAnswer($message, $knowledgeBase);

        $message->update(['answer' => $answerText]);

        return $answerText;
    }
}
