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
            // Obtener el usuario actual desde la solicitud o el parámetro
            $usuarioActualId = $request->input('usuario_actual_id') ?? $request->user()?->id ?? 1;

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

                    // Obtener el último mensaje con este contacto
                    $ultimoMensaje = Mensaje::where(function($query) use ($usuarioActualId, $usuario) {
                        $query->where(function($q) use ($usuarioActualId, $usuario) {
                            $q->where('remitente', $usuarioActualId)
                              ->where('destinatario', $usuario->id);
                        })->orWhere(function($q) use ($usuarioActualId, $usuario) {
                            $q->where('remitente', $usuario->id)
                              ->where('destinatario', $usuarioActualId);
                        });
                    })
                    ->orderBy('fecha', 'desc')
                    ->first();

                    $contactos[] = [
                        'id' => $usuario->id,
                        'nombre' => $persona?->nombre ?? 'N/A',
                        'apellido' => $persona?->apellido_p ?? 'N/A',
                        'email' => $persona?->celular ?? 'N/A',
                        'depa' => $perDep?->id_depa ?? 101,
                        'online' => true,
                        'mensaje' => $ultimoMensaje?->mensaje ?? 'Sin mensajes',
                        'ultima_fecha' => $ultimoMensaje?->fecha ?? null,
                    ];
                } catch (\Exception $e) {
                    \Log::error('Error procesando usuario ' . $usuario->id . ': ' . $e->getMessage());
                    continue;
                }
            }

            // Ordenar contactos por última fecha de mensaje (más reciente primero)
            usort($contactos, function($a, $b) {
                if (!$a['ultima_fecha']) return 1;
                if (!$b['ultima_fecha']) return -1;
                return strtotime($b['ultima_fecha']) - strtotime($a['ultima_fecha']);
            });

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
