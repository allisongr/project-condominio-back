<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Persona;
use App\Models\PerDep;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

/**
 * Controlador de Autenticación
 * 
 * NOTA: El registro público de usuarios ha sido eliminado.
 * Los usuarios solo pueden ser creados por administradores a través del AdminController.
 * Este controlador solo maneja login, logout, verificación de email y obtener datos del usuario autenticado.
 */
class AuthController extends Controller
{
    /**
     * Iniciar sesión
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            $usuario = Usuario::where('email', $validated['email'])->first();

            if (!$usuario || !Hash::check($validated['password'], $usuario->pass)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email o contraseña incorrectos',
                ], 401);
            }

            // Verificar si el email está verificado
            if (!$usuario->email_verified_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Por favor verifica tu correo electrónico antes de iniciar sesión',
                    'email_not_verified' => true,
                ], 403);
            }

            $persona = Persona::find($usuario->id_persona);

            // Verificar si el usuario está activo
            if (!$persona->activo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tu cuenta ha sido desactivada. Contacta al administrador',
                ], 403);
            }

            // Generar un identificador único del dispositivo basado en User-Agent
            $deviceName = $this->getDeviceName($request->header('User-Agent'));
            $token = $usuario->createToken($deviceName)->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Sesión iniciada correctamente',
                'usuario' => [
                    'id' => $usuario->id,
                    'nombre' => $persona->nombre,
                    'apellido' => $persona->apellido_p,
                    'email' => $usuario->email,
                    'admin' => $usuario->admin,
                ],
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en login: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Verificar email
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'token' => 'required|string',
            ]);

            $usuario = Usuario::where('email_verification_token', $validated['token'])->first();

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de verificación inválido',
                ], 400);
            }

            if ($usuario->email_verified_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'El correo ya ha sido verificado',
                ], 400);
            }

            $usuario->update([
                'email_verified_at' => now(),
                'email_verification_token' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Correo verificado exitosamente',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en verificación de email: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Obtener usuario autenticado
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $usuario = $request->user();
            $persona = $usuario->persona;

            return response()->json([
                'success' => true,
                'usuario' => [
                    'id' => $usuario->id,
                    'nombre' => $persona->nombre,
                    'apellido' => $persona->apellido_p,
                    'email' => $usuario->email,
                    'admin' => $usuario->admin,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cerrar sesión
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Eliminar el token actual
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sesión cerrada',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en logout: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cerrar sesión en todos los dispositivos (eliminar todos los tokens)
     */
    public function logoutAllDevices(Request $request): JsonResponse
    {
        try {
            // Eliminar todos los tokens del usuario
            $request->user()->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sesión cerrada en todos los dispositivos',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al cerrar sesión en todos los dispositivos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Obtener lista de dispositivos activos (tokens)
     */
    public function getDevices(Request $request): JsonResponse
    {
        try {
            $usuario = $request->user();
            $currentToken = $request->bearerToken();

            $devices = $usuario->tokens->map(function ($token) use ($currentToken) {
                return [
                    'id' => $token->id,
                    'nombre' => $token->name,
                    'last_used_at' => $token->last_used_at,
                    'created_at' => $token->created_at,
                    'is_current' => $token->plainTextToken === $currentToken,
                ];
            });

            return response()->json([
                'success' => true,
                'devices' => $devices,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener dispositivos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Obtener un nombre legible del dispositivo basado en User-Agent
     */
    private function getDeviceName(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Dispositivo desconocido';
        }

        // Detectar sistema operativo
        $os = 'Desconocido';
        if (stripos($userAgent, 'windows') !== false) {
            $os = 'Windows';
        } elseif (stripos($userAgent, 'mac') !== false) {
            $os = 'macOS';
        } elseif (stripos($userAgent, 'linux') !== false) {
            $os = 'Linux';
        } elseif (stripos($userAgent, 'android') !== false) {
            $os = 'Android';
        } elseif (stripos($userAgent, 'iphone') !== false || stripos($userAgent, 'ipad') !== false) {
            $os = 'iOS';
        }

        // Detectar navegador
        $browser = 'Navegador desconocido';
        if (stripos($userAgent, 'firefox') !== false) {
            $browser = 'Firefox';
        } elseif (stripos($userAgent, 'chrome') !== false) {
            $browser = 'Chrome';
        } elseif (stripos($userAgent, 'safari') !== false) {
            $browser = 'Safari';
        } elseif (stripos($userAgent, 'edge') !== false) {
            $browser = 'Edge';
        }

        return "{$browser} en {$os}";
    }
}
