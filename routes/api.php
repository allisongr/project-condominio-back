<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;

// Auth routes (without middleware)
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/verify-email', [AuthController::class, 'verifyEmail']);

// Protected routes with Sanctum
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout-all-devices', [AuthController::class, 'logoutAllDevices']);
    Route::get('/auth/devices', [AuthController::class, 'getDevices']);

    // Admin routes (protected by admin middleware)
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/usuarios', [AdminController::class, 'index']);
        Route::post('/usuarios', [AdminController::class, 'store']);
        Route::get('/usuarios/{id}', [AdminController::class, 'show']);
        Route::put('/usuarios/{id}', [AdminController::class, 'update']);
        Route::delete('/usuarios/{id}', [AdminController::class, 'destroy']);
        Route::post('/usuarios/{id}/resend-verification', [AdminController::class, 'resendVerification']);
    });

    // Broadcasting auth endpoint (for WebSocket private channels)
    Route::post('/broadcasting/auth', function (Request $request) {
        $usuario = $request->user();
        
        \Log::info('Broadcasting auth request', [
            'usuario_id' => $usuario->id,
            'channel_name' => $request->input('channel_name'),
            'socket_id' => $request->input('socket_id'),
        ]);
        
        if (!$usuario) {
            \Log::error('Broadcasting auth: Usuario no autenticado');
            return response()->json(['error' => 'Usuario no autenticado'], 403);
        }
        
        \Log::info('Broadcasting auth: Usuario autenticado', ['usuario_id' => $usuario->id]);
        
        $response = Broadcast::auth($request);
        \Log::info('Broadcasting auth response', ['response' => $response]);
        
        return $response;
    });

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
    
    // Debug endpoints
    Route::get('/chat/debug/messages', [ChatController::class, 'debugMessages']);
    Route::delete('/chat/debug/clear', [ChatController::class, 'clearDebugMessages']);
    
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
