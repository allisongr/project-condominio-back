<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Persona;
use App\Models\PerDep;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Registrar un nuevo usuario
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:100',
                'apellido_p' => 'required|string|max:100',
                'apellido_m' => 'nullable|string|max:100',
                'celular' => 'nullable|numeric',
                'email' => 'required|email|unique:usuarios,email',
                'password' => 'required|string|min:6',
            ]);

            // Crear persona
            $persona = Persona::create([
                'nombre' => $validated['nombre'],
                'apellido_p' => $validated['apellido_p'],
                'apellido_m' => $validated['apellido_m'] ?? null,
                'celular' => $validated['celular'] ?? null,
            ]);

            // Crear usuario
            $usuario = Usuario::create([
                'id_persona' => $persona->id,
                'email' => $validated['email'],
                'pass' => bcrypt($validated['password']),
                'admin' => false,
            ]);

            // Asignar al primer departamento disponible
            $depa = PerDep::first();
            if ($depa) {
                PerDep::create([
                    'id_persona' => $persona->id,
                    'id_depa' => $depa->id_depa,
                    'id_rol' => 1, // Residente
                    'residente' => true,
                ]);
            }

            // Generar token
            $token = 'token_' . uniqid();

            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado exitosamente',
                'usuario' => [
                    'id' => $usuario->id,
                    'nombre' => $persona->nombre,
                    'apellido' => $persona->apellido_p,
                    'email' => $validated['email'],
                ],
                'token' => $token,
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Error en registro: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

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

            // Buscar usuario por email (usando la tabla usuarios, campo email)
            // Nota: Necesitaremos agregar email a la tabla usuarios
            $usuario = Usuario::where('email', $validated['email'])->first();

            if (!$usuario || !Hash::check($validated['password'], $usuario->pass)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email o contraseña incorrectos',
                ], 401);
            }

            $persona = Persona::find($usuario->id_persona);

            // Generar token
            $token = 'token_' . uniqid();

            return response()->json([
                'success' => true,
                'message' => 'Sesión iniciada correctamente',
                'usuario' => [
                    'id' => $usuario->id,
                    'nombre' => $persona->nombre,
                    'apellido' => $persona->apellido_p,
                    'email' => $usuario->email,
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
     * Cerrar sesión
     */
    public function logout(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada',
        ]);
    }
}
