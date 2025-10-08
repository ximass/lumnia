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
use App\Http\Controllers\SourceController;
use App\Http\Controllers\ChunkController;
use App\Http\Controllers\SourceProcessingController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\MessageRatingController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ErrorLogController;

Route::get('/status', function () {
    return response()->json(['message' => 'API is running']);
});


Route::middleware('web')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/register', [AuthController::class, 'register']);
});

Route::get('/avatars/{filename}', [UserController::class, 'serveAvatar']);
Route::get('/personas/active', [PersonaController::class, 'getActivePersonas']);
Route::post('/chunks/search', [ChunkController::class, 'search']);

// Rotas protegidas com autenticação
Route::middleware('auth:sanctum')->group(function () {
    // User routes
    Route::get('/user', function (Request $request) {
        return $request->user()->load('userPersona');
    });

    // Chat routes
    Route::get('/chats', [ChatController::class, 'getChats']);
    Route::get('/chats/{chat}/messages', [ChatController::class, 'getMessages']);
    Route::get('/chats/{chat}/context', [ChatController::class, 'getContextInfo']);
    Route::post('/chat', [ChatController::class, 'createChat']);
    Route::post('/chat/{chat}', [ChatController::class, 'sendMessage'])
        ->middleware(\App\Http\Middleware\DisableBuffering::class);
    Route::put('/chat/{chat}', [ChatController::class, 'updateChat']);
    Route::delete('/chat/{chat}', [ChatController::class, 'deleteChat']);
    Route::delete('/chat/{chat}/context', [ChatController::class, 'clearContext']);

    // User Persona routes
    Route::get('/user-persona', [UserPersonaController::class, 'show']);
    Route::post('/user-persona', [UserPersonaController::class, 'store']);
    Route::put('/user-persona', [UserPersonaController::class, 'update']);
    Route::delete('/user-persona', [UserPersonaController::class, 'destroy']);

    // User management routes
    Route::get('/users/search', [UserController::class, 'search']);
    Route::put('/user/{user}', [UserController::class, 'updateUser']);
    Route::post('/user/{user}/profile', [UserController::class, 'updateProfile']);

    // Group routes
    Route::get('/groups/search', [GroupController::class, 'search']);

    // Knowledge Base routes
    Route::put('/knowledge-base/{knowledgeBase}', [KnowledgeBaseController::class, 'updateKnowledgeBase']);
    Route::get('/knowledge-bases-user', [KnowledgeBaseController::class, 'getKnowledgeBases']);

    // Source processing routes
    Route::post('/sources/upload', [SourceProcessingController::class, 'uploadAndProcess']);
    Route::post('/sources/{source}/process', [SourceProcessingController::class, 'processExisting']);
    Route::get('/sources/{source}/status', [SourceProcessingController::class, 'getStatus']);
    Route::post('/sources/{source}/retry', [SourceProcessingController::class, 'retry']);

    // Search route
    Route::post('/search', [SearchController::class, 'search']);
    
    // Information Sources routes
    Route::get('/messages/{message}/information-sources', [MessageController::class, 'getInformationSources']);

    // Message Rating routes
    Route::get('/messages/{message}/rating', [MessageRatingController::class, 'show']);
    Route::post('/messages/{message}/rating', [MessageRatingController::class, 'store']);
    Route::delete('/messages/{message}/rating', [MessageRatingController::class, 'destroy']);

    Route::get('/error-logs/statistics', [ErrorLogController::class, 'statistics']);
    Route::delete('/error-logs/destroy-all', [ErrorLogController::class, 'destroyAll']);
    Route::apiResource('/error-logs', ErrorLogController::class)->only(['index', 'show', 'destroy']);

    // Resource routes (CRUD completo)
    Route::apiResource('/groups', GroupController::class);
    Route::apiResource('/users', UserController::class);
    Route::apiResource('/personas', PersonaController::class);
    Route::apiResource('/knowledge-bases', KnowledgeBaseController::class);
    Route::apiResource('/sources', SourceController::class);
    Route::apiResource('/chunks', ChunkController::class);
});
