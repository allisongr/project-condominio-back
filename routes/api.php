<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthController;

// Auth routes (without middleware)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout']);

Route::middleware('api')->group(function () {
    // Broadcasting auth endpoint (for WebSocket private channels)
    Route::post('/broadcasting/auth', function (Request $request) {
        // Get user ID from request (sent from frontend)
        $usuarioId = $request->input('usuario_id') ?? $request->header('X-Usuario-Id');
        
        \Log::info('Broadcasting auth request', [
            'usuario_id_input' => $request->input('usuario_id'),
            'usuario_id_header' => $request->header('X-Usuario-Id'),
            'channel_name' => $request->input('channel_name'),
            'socket_id' => $request->input('socket_id'),
            'all_input' => $request->all(),
            'all_headers' => $request->headers->all(),
        ]);
        
        if (!$usuarioId) {
            \Log::error('Broadcasting auth: Usuario no autenticado');
            return response()->json(['error' => 'Usuario no autenticado'], 403);
        }
        
        // Create a mock user for broadcasting authorization
        $usuario = \App\Models\Usuario::find($usuarioId);
        if (!$usuario) {
            \Log::error('Broadcasting auth: Usuario no encontrado', ['usuario_id' => $usuarioId]);
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }
        
        \Log::info('Broadcasting auth: Usuario autenticado', ['usuario_id' => $usuario->id]);
        
        // Manually authenticate the user for this request
        \Auth::setUser($usuario);
        
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
