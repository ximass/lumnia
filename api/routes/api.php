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

##GET##
Route::get('/user', function (Request $request) {
    return $request->user()->load('userPersona');
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->get('/chats', [ChatController::class, 'getChats']);
Route::middleware('auth:sanctum')->get('/user-persona', [UserPersonaController::class, 'show']);

Route::get('/chats/{chat}/messages', [ChatController::class, 'getMessages']);
Route::get('/knowledge-bases', [KnowledgeBaseController::class, 'getKnowledgeBases']);
Route::get('/users/search', [UserController::class, 'search']);
Route::get('/avatars/{filename}', [UserController::class, 'serveAvatar']);
// Route::get('/message/{message}/information-sources', [MessageController::class, 'getInformationSources']);
Route::get('/personas/active', [PersonaController::class, 'getActivePersonas']);

Route::apiResource('/groups', GroupController::class);
Route::apiResource('/users', UserController::class);
Route::apiResource('/personas', PersonaController::class);

##POST##
Route::middleware('web')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth:sanctum')->post('/chat/{chat}', [ChatController::class, 'sendMessage']);
Route::middleware('auth:sanctum')->post('/chat', [ChatController::class, 'createChat']);
Route::middleware('auth:sanctum')->post('/user-persona', [UserPersonaController::class, 'store']);

##PUT##

Route::put('/chat/{chat}', [ChatController::class, 'updateChat']);
Route::put('/user/{user}', [UserController::class, 'updateUser']);
Route::post('/user/{user}/profile', [UserController::class, 'updateProfile'])->middleware('auth:sanctum');
Route::put('/knowledge-base/{knowledgeBase}', [KnowledgeBaseController::class, 'updateKnowledgeBase']);
Route::middleware('auth:sanctum')->put('/user-persona', [UserPersonaController::class, 'update']);

##DELETE##
Route::delete('/chat/{chat}', [ChatController::class, 'deleteChat']);
Route::middleware('auth:sanctum')->delete('/user-persona', [UserPersonaController::class, 'destroy']);