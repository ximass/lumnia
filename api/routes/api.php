<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KnowledgeBaseController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPersonaController;
use App\Http\Controllers\PersonaController;

Route::middleware('web')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/register', [AuthController::class, 'register']);
});

Route::get('/avatars/{filename}', [UserController::class, 'serveAvatar']);
Route::get('/knowledge-bases', [KnowledgeBaseController::class, 'getKnowledgeBases']);
Route::get('/personas/active', [PersonaController::class, 'getActivePersonas']);

// Rotas protegidas com autenticação
Route::middleware('auth:sanctum')->group(function () {
    // User routes
    Route::get('/user', function (Request $request) {
        return $request->user()->load('userPersona');
    });
    
    // Chat routes
    Route::get('/chats', [ChatController::class, 'getChats']);
    Route::get('/chats/{chat}/messages', [ChatController::class, 'getMessages']);
    Route::post('/chat', [ChatController::class, 'createChat']);
    Route::post('/chat/{chat}', [ChatController::class, 'sendMessage']);
    Route::put('/chat/{chat}', [ChatController::class, 'updateChat']);
    Route::delete('/chat/{chat}', [ChatController::class, 'deleteChat']);
    
    // User Persona routes
    Route::get('/user-persona', [UserPersonaController::class, 'show']);
    Route::post('/user-persona', [UserPersonaController::class, 'store']);
    Route::put('/user-persona', [UserPersonaController::class, 'update']);
    Route::delete('/user-persona', [UserPersonaController::class, 'destroy']);
    
    // User management routes
    Route::get('/users/search', [UserController::class, 'search']);
    Route::put('/user/{user}', [UserController::class, 'updateUser']);
    Route::post('/user/{user}/profile', [UserController::class, 'updateProfile']);
    
    // Knowledge Base routes
    Route::put('/knowledge-base/{knowledgeBase}', [KnowledgeBaseController::class, 'updateKnowledgeBase']);
    
    // Resource routes (CRUD completo)
    Route::apiResource('/groups', GroupController::class);
    Route::apiResource('/users', UserController::class);
    Route::apiResource('/personas', PersonaController::class);
});