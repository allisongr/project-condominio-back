<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

Route::middleware('api')->group(function () {
    /**
     * Chat Routes - WebSocket enabled
     */
    
    // Send a new message
    Route::post('/chat/send', [ChatController::class, 'sendMessage']);
    
    // Get messages for a departamento
    Route::get('/chat/{id_depa}/messages', [ChatController::class, 'getMessages']);
    
    // Mark message as read
    Route::put('/chat/{mensaje_id}/read', [ChatController::class, 'markAsRead']);
    
    // Broadcast typing indicator
    Route::post('/chat/typing', [ChatController::class, 'typing']);
    
    // Get unread message count for a user
    Route::get('/chat/{usuario_id}/unread', [ChatController::class, 'getUnreadCount']);
    
    // Delete a message
    Route::delete('/chat/{mensaje_id}', [ChatController::class, 'deleteMessage']);
});
