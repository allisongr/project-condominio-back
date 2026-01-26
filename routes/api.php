<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\UsuarioController;

Route::middleware('api')->group(function () {
    /**
     * Usuario Routes
     */
    Route::get('/usuarios/contactos', [UsuarioController::class, 'getContactos']);
    Route::get('/usuarios/{id}', [UsuarioController::class, 'show']);
    Route::put('/usuarios/{id}/online-status', [UsuarioController::class, 'updateOnlineStatus']);
    Route::get('/usuarios/{id}/departamento', [UsuarioController::class, 'getDepartamento']);

    /**
     * Chat Routes - WebSocket enabled
     */
    
    // Send a new message
    Route::post('/chat/send', [ChatController::class, 'sendMessage']);
    
    // Get messages for a departamento and optionally filter by contacto
    Route::get('/chat/messages', [ChatController::class, 'getMessages']);
    
    // Get messages for a specific departamento (legacy route)
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
