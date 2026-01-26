<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Persona;
use App\Models\PerDep;
use App\Models\Departamento;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UsuarioController extends Controller
{
    /**
     * Get all contacts for the current user
     */
    public function getContactos(Request $request): JsonResponse
    {
        try {
            // Obtener el usuario actual
            $usuarioActualId = $request->user()?->id ?? 1;

            // Obtener todos los usuarios
            $usuarios = Usuario::all();

            $contactos = [];
            
            foreach ($usuarios as $usuario) {
                if ($usuario->id == $usuarioActualId) {
                    continue;
                }

                try {
                    $persona = Persona::find($usuario->id_persona);
                    $perDep = PerDep::where('id_persona', $usuario->id_persona)->first();

                    $contactos[] = [
                        'id' => $usuario->id,
                        'nombre' => $persona?->nombre ?? 'N/A',
                        'apellido' => $persona?->apellido_p ?? 'N/A',
                        'email' => $persona?->celular ?? 'N/A',
                        'depa' => $perDep?->id_depa ?? 101,
                        'online' => true,
                        'mensaje' => 'Sin mensajes',
                    ];
                } catch (\Exception $e) {
                    \Log::error('Error procesando usuario ' . $usuario->id . ': ' . $e->getMessage());
                    continue;
                }
            }

            return response()->json($contactos);
        } catch (\Exception $e) {
            \Log::error('Error en getContactos: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Get a specific usuario by ID
     */
    public function show($id): JsonResponse
    {
        try {
            $usuario = Usuario::with('persona')->find($id);

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $perDep = PerDep::where('id_persona', $usuario->id_persona)->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $usuario->id,
                    'nombre' => $usuario->persona->nombre ?? 'N/A',
                    'apellido' => $usuario->persona->apellido_p ?? 'N/A',
                    'email' => $usuario->persona->celular ?? 'N/A',
                    'id_depa' => $perDep?->id_depa ?? 101,
                    'rol' => $perDep?->rol ?? 'Usuario',
                    'en_linea' => true,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user online status
     */
    public function updateOnlineStatus(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'en_linea' => 'required|boolean',
            ]);

            $usuario = Usuario::find($id);

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            // Aquí podrías guardar el estado en cache o en una tabla específica
            // Por ahora solo devolvemos que se actualizó

            return response()->json([
                'success' => true,
                'message' => 'Estado de usuario actualizado',
                'data' => [
                    'en_linea' => $validated['en_linea'],
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's departamento info
     */
    public function getDepartamento($id): JsonResponse
    {
        try {
            $usuario = Usuario::find($id);

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $perDep = PerDep::where('id_persona', $usuario->id_persona)->first();
            
            if (!$perDep) {
                return response()->json([
                    'success' => false,
                    'message' => 'Departamento no encontrado para el usuario'
                ], 404);
            }

            $departamento = Departamento::find($perDep->id_depa);

            if (!$departamento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Departamento no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $departamento->id,
                    'depa' => $departamento->depa,
                    'moroso' => $departamento->moroso,
                    'codigo' => $departamento->codigo,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
