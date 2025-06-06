<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KnowledgeBaseController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MessageController;

##GET##
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->get('/chats', [ChatController::class, 'getChats']);

Route::get('/chats/{chat}/messages', [ChatController::class, 'getMessages']);
Route::get('/knowledge-bases', [KnowledgeBaseController::class, 'getKnowledgeBases']);
Route::get('/users/search', [UserController::class, 'search']);
Route::get('/message/{message}/information-sources', [MessageController::class, 'getInformationSources']);

Route::apiResource('/groups', GroupController::class);
Route::apiResource('/users', UserController::class);

##POST##
Route::middleware('web')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth:sanctum')->post('/chat/{chat}', [ChatController::class, 'sendMessage']);
Route::middleware('auth:sanctum')->post('/chat', [ChatController::class, 'createChat']);

##PUT##

Route::put('/chat/{chat}', [ChatController::class, 'updateChat']);
Route::put('/user/{user}', [UserController::class, 'updateUser']);
Route::put('/knowledge-base/{knowledgeBase}', [KnowledgeBaseController::class, 'updateKnowledgeBase']);

##DELETE##
Route::delete('/chat/{chat}', [ChatController::class, 'deleteChat']);