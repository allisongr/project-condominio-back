<?php

namespace App\Http\Controllers;

use App\Events\MensajeEnviado;
use App\Events\UsuarioEscribiendo;
use App\Models\Mensaje;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChatController extends Controller
{
    /**
     * Send a message
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'remitente_id' => 'required|integer',
            'destinatario_id' => 'required|integer',
            'id_depa' => 'required|integer',
            'contenido' => 'required|string|max:1000',
            'tipo' => 'required|in:personal,departamento,general',
        ]);

        try {
            $mensaje = Mensaje::create([
                'remitente' => $validated['remitente_id'],
                'destinatario' => $validated['destinatario_id'],
                'id_depaR' => $validated['id_depa'],
                'id_depaD' => $validated['id_depa'],
                'mensaje' => $validated['contenido'],
                'tipo' => $validated['tipo'],
                'leido' => false,
                'fecha' => now(),
            ]);

            // Broadcast the message to connected users
            MensajeEnviado::dispatch($mensaje);

            return response()->json([
                'success' => true,
                'message' => 'Mensaje enviado',
                'data' => [
                    'id' => (string)$mensaje->_id,
                    'remitente_id' => $mensaje->remitente,
                    'contenido' => $mensaje->mensaje,
                    'fecha' => $mensaje->fecha,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Error al enviar mensaje'
            ], 500);
        }
    }

    /**
     * Get messages for a departamento
     */
    public function getMessages(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id_depa' => 'required|integer',
                'contacto_id' => 'nullable|integer',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50',
            ]);

            $page = $validated['page'] ?? 1;
            $per_page = $validated['per_page'] ?? 50;
            $skip = ($page - 1) * $per_page;

            $query = Mensaje::where(function ($q) use ($validated) {
                $q->where('id_depaR', $validated['id_depa'])
                  ->orWhere('id_depaD', $validated['id_depa']);
            });

            // If contacto_id provided, filter personal messages between two users
            if (isset($validated['contacto_id']) && $validated['contacto_id']) {
                $usuarioActualId = $validated['usuario_id'] ?? 999;
                
                $query->where(function ($q) use ($validated, $usuarioActualId) {
                    // Messages between current user and contacto
                    $q->where(function ($subQ) use ($validated, $usuarioActualId) {
                        $subQ->where('remitente', $usuarioActualId)
                             ->where('destinatario', $validated['contacto_id']);
                    })->orWhere(function ($subQ) use ($validated, $usuarioActualId) {
                        $subQ->where('remitente', $validated['contacto_id'])
                             ->where('destinatario', $usuarioActualId);
                    });
                });
            }

            $total = $query->count();
            $mensajes = $query->orderBy('fecha', 'asc')
                ->skip($skip)
                ->take($per_page)
                ->get()
                ->map(function ($mensaje) {
                    return [
                        'id' => (string)$mensaje->_id,
                        'remitente_id' => $mensaje->remitente,
                        'destinatario_id' => $mensaje->destinatario,
                        'contenido' => $mensaje->mensaje,
                        'tipo' => $mensaje->tipo,
                        'id_depa' => $mensaje->id_depaR,
                        'leido' => $mensaje->leido,
                        'fecha' => $mensaje->fecha,
                    ];
                });

            return response()->json($mensajes);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Error al obtener mensajes'
            ], 500);
        }
    }

    /**
     * Mark message as read
     */
    public function markAsRead(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mensaje_id' => 'required|string',
        ]);

        $mensaje = Mensaje::find($validated['mensaje_id']);

        if (!$mensaje) {
            return response()->json([
                'success' => false,
                'message' => 'Mensaje no encontrado'
            ], 404);
        }

        $mensaje->update(['leido' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Mensaje marcado como leído'
        ]);
    }

    /**
     * Broadcast typing indicator
     */
    public function typing(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'usuario_id' => 'required|integer',
                'destinatario_id' => 'required|integer',
                'id_depa' => 'required|integer',
            ]);

            // Broadcast the typing event
            UsuarioEscribiendo::dispatch(
                $validated['usuario_id'],
                $validated['destinatario_id'],
                $validated['id_depa']
            );

            return response()->json([
                'success' => true,
                'message' => 'Typing indicator enviado'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get unread message count
     */
    public function getUnreadCount(Request $request, $usuario_id): JsonResponse
    {
        $validated = $request->validate([
            'id_depa' => 'required|integer',
        ]);

        $unread = Mensaje::where('id_depa', $validated['id_depa'])
            ->where('destinatario_id', $usuario_id)
            ->where('leido', false)
            ->count();

        return response()->json([
            'success' => true,
            'unread_count' => $unread
        ]);
    }

    /**
     * Delete a message (soft delete)
     */
    public function deleteMessage(Request $request, $mensaje_id): JsonResponse
    {
        $mensaje = Mensaje::find($mensaje_id);

        if (!$mensaje) {
            return response()->json([
                'success' => false,
                'message' => 'Mensaje no encontrado'
            ], 404);
        }

        // Add deleted_at field to soft delete
        $mensaje->update(['deleted_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Mensaje eliminado'
        ]);
    }
}
