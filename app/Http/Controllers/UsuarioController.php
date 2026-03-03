<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Persona;
use App\Models\PerDep;
use App\Models\Departamento;
use App\Models\Mensaje;
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
            // Obtener el usuario autenticado o del parámetro
            $usuarioActual = $request->user();
            
            if (!$usuarioActual) {
                \Log::error('getContactos: No autenticado');
                return response()->json([
                    'error' => 'No autorizado'
                ], 401);
            }

            $usuarioActualId = $usuarioActual->id;
            \Log::info('getContactos: Usuario actual', ['id' => $usuarioActualId]);

            // Obtener todos los usuarios excepto el actual
            $usuarios = Usuario::where('id', '!=', $usuarioActualId)
                ->with('persona')
                ->get();

            $contactos = [];
            
            foreach ($usuarios as $usuario) {
                try {
                    $persona = $usuario->persona;
                    
                    if (!$persona) {
                        \Log::warning('Usuario sin persona', ['usuario_id' => $usuario->id]);
                        continue;
                    }

                    // Obtener el departamento del usuario
                    $perDep = PerDep::where('id_persona', $usuario->id_persona)->first();
                    $depa = $perDep?->id_depa ?? 101;

                    $contactos[] = [
                        'id' => $usuario->id,
                        'nombre' => $persona->nombre ?? 'N/A',
                        'apellido' => $persona->apellido_p ?? 'N/A',
                        'apellido_m' => $persona->apellido_m ?? '',
                        'email' => $persona->email ?? $persona->celular ?? 'N/A',
                        'celular' => $persona->celular ?? '',
                        'depa' => $depa,
                        'online' => true,
                    ];
                } catch (\Exception $e) {
                    \Log::error('Error procesando usuario', [
                        'usuario_id' => $usuario->id,
                        'error' => $e->getMessage()
                    ]);
                    continue;
                }
            }

            \Log::info('getContactos: Contactos cargados', ['count' => count($contactos)]);

            return response()->json($contactos);
        } catch (\Exception $e) {
            \Log::error('Error en getContactos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Error al cargar contactos',
                'message' => $e->getMessage()
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
