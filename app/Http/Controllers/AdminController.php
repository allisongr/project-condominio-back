<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Persona;
use App\Models\PerDep;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    /**
     * Listar todos los usuarios
     */
    public function index(): JsonResponse
    {
        try {
            $usuarios = Usuario::with(['persona', 'persona.perDeps.rol'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($usuario) {
                    return [
                        'id' => $usuario->id,
                        'nombre' => $usuario->persona->nombre,
                        'apellido_p' => $usuario->persona->apellido_p,
                        'apellido_m' => $usuario->persona->apellido_m,
                        'celular' => $usuario->persona->celular,
                        'email' => $usuario->email,
                        'admin' => $usuario->admin,
                        'email_verified' => $usuario->email_verified_at ? true : false,
                        'email_verified_at' => $usuario->email_verified_at,
                        'activo' => $usuario->persona->activo,
                        'created_at' => $usuario->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'usuarios' => $usuarios,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al listar usuarios: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los usuarios',
            ], 500);
        }
    }

    /**
     * Crear un nuevo usuario (solo admin)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:100',
                'apellido_p' => 'required|string|max:100',
                'apellido_m' => 'nullable|string|max:100',
                'celular' => 'nullable|numeric',
                'email' => 'required|email|unique:usuarios,email',
                'password' => 'required|string|min:6',
                'admin' => 'nullable|boolean',
                'id_depa' => 'nullable|exists:departamentos,id',
                'id_rol' => 'nullable|exists:roles,id',
            ]);

            // Crear persona
            $persona = Persona::create([
                'nombre' => $validated['nombre'],
                'apellido_p' => $validated['apellido_p'],
                'apellido_m' => $validated['apellido_m'] ?? null,
                'celular' => $validated['celular'] ?? null,
                'activo' => true,
            ]);

            // Generar token de verificación
            $verificationToken = Str::random(64);

            // Crear usuario
            $usuario = Usuario::create([
                'id_persona' => $persona->id,
                'email' => $validated['email'],
                'pass' => bcrypt($validated['password']),
                'admin' => $validated['admin'] ?? false,
                'email_verification_token' => $verificationToken,
            ]);

            // Asignar departamento y rol si se proporcionan
            if (isset($validated['id_depa']) && isset($validated['id_rol'])) {
                PerDep::create([
                    'id_persona' => $persona->id,
                    'id_depa' => $validated['id_depa'],
                    'id_rol' => $validated['id_rol'],
                    'residente' => $validated['id_rol'] == 1,
                ]);
            }

            // Enviar email de verificación
            $usuario->notify(new VerifyEmailNotification($verificationToken));

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado exitosamente. Se ha enviado un correo de verificación.',
                'usuario' => [
                    'id' => $usuario->id,
                    'nombre' => $persona->nombre,
                    'apellido' => $persona->apellido_p,
                    'email' => $validated['email'],
                    'admin' => $usuario->admin,
                ],
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Error al crear usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Obtener un usuario específico
     */
    public function show($id): JsonResponse
    {
        try {
            $usuario = Usuario::with(['persona', 'persona.perDeps.rol'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'usuario' => [
                    'id' => $usuario->id,
                    'nombre' => $usuario->persona->nombre,
                    'apellido_p' => $usuario->persona->apellido_p,
                    'apellido_m' => $usuario->persona->apellido_m,
                    'celular' => $usuario->persona->celular,
                    'email' => $usuario->email,
                    'admin' => $usuario->admin,
                    'email_verified' => $usuario->email_verified_at ? true : false,
                    'email_verified_at' => $usuario->email_verified_at,
                    'activo' => $usuario->persona->activo,
                    'created_at' => $usuario->created_at,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado',
            ], 404);
        }
    }

    /**
     * Actualizar un usuario
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $usuario = Usuario::with('persona')->findOrFail($id);

            $validated = $request->validate([
                'nombre' => 'sometimes|required|string|max:100',
                'apellido_p' => 'sometimes|required|string|max:100',
                'apellido_m' => 'nullable|string|max:100',
                'celular' => 'nullable|numeric',
                'email' => 'sometimes|required|email|unique:usuarios,email,' . $id,
                'password' => 'nullable|string|min:6',
                'admin' => 'nullable|boolean',
                'activo' => 'nullable|boolean',
            ]);

            // Actualizar persona
            $persona = $usuario->persona;
            $persona->update([
                'nombre' => $validated['nombre'] ?? $persona->nombre,
                'apellido_p' => $validated['apellido_p'] ?? $persona->apellido_p,
                'apellido_m' => $validated['apellido_m'] ?? $persona->apellido_m,
                'celular' => $validated['celular'] ?? $persona->celular,
                'activo' => $validated['activo'] ?? $persona->activo,
            ]);

            // Actualizar usuario
            $updateData = [];
            if (isset($validated['email'])) {
                $updateData['email'] = $validated['email'];
            }
            if (isset($validated['password'])) {
                $updateData['pass'] = bcrypt($validated['password']);
                // Eliminar todos los tokens del usuario (cerrar sesión en todos los dispositivos)
                $usuario->tokens()->delete();
            }
            if (isset($validated['admin'])) {
                $updateData['admin'] = $validated['admin'];
            }

            if (!empty($updateData)) {
                $usuario->update($updateData);
            }

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente',
                'usuario' => [
                    'id' => $usuario->id,
                    'nombre' => $persona->nombre,
                    'apellido' => $persona->apellido_p,
                    'email' => $usuario->email,
                    'admin' => $usuario->admin,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al actualizar usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Eliminar un usuario
     */
    public function destroy($id): JsonResponse
    {
        try {
            $usuario = Usuario::findOrFail($id);
            $persona = $usuario->persona;

            // Eliminar el usuario (cascade eliminará persona)
            $usuario->delete();

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al eliminar usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el usuario',
            ], 500);
        }
    }

    /**
     * Reenviar email de verificación
     */
    public function resendVerification($id): JsonResponse
    {
        try {
            $usuario = Usuario::findOrFail($id);

            if ($usuario->email_verified_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'El correo ya ha sido verificado',
                ], 400);
            }

            // Generar nuevo token
            $verificationToken = Str::random(64);
            $usuario->update([
                'email_verification_token' => $verificationToken,
            ]);

            // Enviar email de verificación
            $usuario->notify(new VerifyEmailNotification($verificationToken));

            return response()->json([
                'success' => true,
                'message' => 'Se ha reenviado el correo de verificación',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al reenviar verificación: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al reenviar el correo',
            ], 500);
        }
    }
}
