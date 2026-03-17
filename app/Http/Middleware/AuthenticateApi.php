<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class AuthenticateApi
{
    /**
     * Handle an incoming request for API authentication.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Obtener el token Bearer del header
        $token = $request->bearerToken();
        
        if (!$token) {
            \Log::warning('AuthenticateApi: Sin token Bearer', [
                'path' => $request->path(),
                'method' => $request->method()
            ]);
            
            return response()->json([
                'error' => 'No autenticado',
                'message' => 'Se requiere un token válido para acceder a este recurso'
            ], 401);
        }
        
        // Verificar el token de Sanctum
        $personalAccessToken = PersonalAccessToken::findToken($token);
        
        if (!$personalAccessToken || $personalAccessToken->tokenable === null) {
            \Log::warning('AuthenticateApi: Token inválido o revocado', [
                'token' => substr($token, 0, 10) . '...',
                'path' => $request->path()
            ]);
            
            return response()->json([
                'error' => 'No autenticado',
                'message' => 'Token inválido o expirado'
            ], 401);
        }
        
        // Establecer el usuario en la request
        $request->setUserResolver(fn () => $personalAccessToken->tokenable);
        
        \Log::info('AuthenticateApi: Autenticación exitosa', [
            'usuario_id' => $personalAccessToken->tokenable->id,
            'path' => $request->path()
        ]);
        
        return $next($request);
    }
}
