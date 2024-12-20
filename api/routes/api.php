<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AuthController;

##GET##
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->get('/chats', [ChatController::class, 'getChats']);
Route::get('/chats/{chat}/messages', [ChatController::class, 'getMessages']);

##POST##
Route::middleware('web')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth:sanctum')->post('/chat/{chat}', [ChatController::class, 'sendMessage']);