<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;

// Rutas de autenticación (sin middleware)
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/verify-email', [AuthController::class, 'verifyEmail']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/verify-reset-code', [AuthController::class, 'verifyResetCode']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

// Endpoint de autenticación de transmisión (para canales privados de WebSocket)
// Se mantiene sin middleware porque Pusher hace una request directa
Route::post('/broadcasting/auth', function (Request $request) {
    try {
        // Obtener el token del header Authorization
        $token = $request->bearerToken();
        
        if (!$token) {
            \Log::error('Broadcasting auth: Sin token Bearer');
            return response()->json(['error' => 'Sin token de autenticación'], 401);
        }
        
        // Verificar el token de Sanctum directamente
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        
        if (!$personalAccessToken || $personalAccessToken->tokenable === null) {
            \Log::error('Broadcasting auth: Token inválido');
            return response()->json(['error' => 'Token inválido'], 401);
        }
        
        $usuario = $personalAccessToken->tokenable;
        
        \Log::info('Broadcasting auth: Usuario autenticado', [
            'usuario_id' => $usuario->id,
            'channel_name' => $request->input('channel_name'),
        ]);
        
        // Establecer el usuario en la request para Broadcast::auth()
        $request->setUserResolver(fn () => $usuario);
        
        // Broadcaster auth - authenticates access to private channels
        $response = Broadcast::auth($request);
        
        return $response;
    } catch (\Exception $e) {
        \Log::error('Broadcasting auth: Error', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        
        return response()->json([
            'error' => 'Error de autenticación',
            'message' => $e->getMessage()
        ], 500);
    }
});

// Rutas protegidas con Sanctum
Route::middleware('auth.api')->group(function () {
    // Rutas de autenticación
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout-all-devices', [AuthController::class, 'logoutAllDevices']);
    Route::get('/auth/devices', [AuthController::class, 'getDevices']);
    Route::delete('/auth/devices/{deviceId}', [AuthController::class, 'deleteDevice']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

    // Rutas de administrador (protegidas por middleware de admin)
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/usuarios', [AdminController::class, 'index']);
        Route::post('/usuarios', [AdminController::class, 'store']);
        Route::get('/usuarios/{id}', [AdminController::class, 'show']);
        Route::put('/usuarios/{id}', [AdminController::class, 'update']);
        Route::delete('/usuarios/{id}', [AdminController::class, 'destroy']);
        Route::post('/usuarios/{id}/resend-verification', [AdminController::class, 'resendVerification']);
    });

    /**
     * Rutas de Usuario
     */
    Route::get('/usuarios/contactos', [UsuarioController::class, 'getContactos']);
    Route::get('/usuarios/{id}', [UsuarioController::class, 'show']);
    Route::put('/usuarios/{id}/online-status', [UsuarioController::class, 'updateOnlineStatus']);
    Route::get('/usuarios/{id}/departamento', [UsuarioController::class, 'getDepartamento']);

    /**
     * Rutas de Chat - WebSocket habilitado
     */
    
    // Endpoints de depuración
    Route::get('/chat/debug/messages', [ChatController::class, 'debugMessages']);
    Route::delete('/chat/debug/clear', [ChatController::class, 'clearDebugMessages']);
    
    // Enviar un nuevo mensaje
    Route::post('/chat/send', [ChatController::class, 'sendMessage']);
    
    // Obtener mensajes para un departamento y opcionalmente filtrar por contacto
    Route::get('/chat/messages', [ChatController::class, 'getMessages']);
    
    // Obtener mensajes para un departamento específico (ruta heredada)
    Route::get('/chat/{id_depa}/messages', [ChatController::class, 'getMessages']);
    
    // Marcar mensaje como leído
    Route::put('/chat/{mensaje_id}/read', [ChatController::class, 'markAsRead']);
    
    // Transmitir indicador de escritura
    Route::post('/chat/typing', [ChatController::class, 'typing']);
    
    // Obtener el conteo de mensajes no leídos para un usuario
    Route::get('/chat/unread', [ChatController::class, 'getUnreadCount']);
    
    // Eliminar un mensaje
    Route::delete('/chat/{mensaje_id}', [ChatController::class, 'deleteMessage']);
});
