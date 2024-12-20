<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

##GET##
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::get('/chats', [ChatController::class, 'getChats']);
Route::get('/chats/{chat}/messages', [ChatController::class, 'getMessages']);

##POST##
Route::post('/chat', [ChatController::class, 'sendMessage']);
Route::post('/chats', [ChatController::class, 'createChat']);