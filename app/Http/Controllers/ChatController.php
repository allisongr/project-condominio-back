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
                'remitente' => (int)$validated['remitente_id'],
                'destinatario' => (int)$validated['destinatario_id'],
                'id_depaR' => (int)$validated['id_depa'],
                'id_depaD' => (int)$validated['id_depa'],
                'mensaje' => $validated['contenido'],
                'tipo' => $validated['tipo'],
                'leido' => false,
                'fecha' => now(),
            ]);

            MensajeEnviado::dispatch($mensaje);

            return response()->json([
                'success' => true,
                'message' => 'Mensaje enviado',
                'data' => [
                    'id' => (string)$mensaje->_id,
                    'remitente_id' => $mensaje->remitente,
                    'destinatario_id' => $mensaje->destinatario,
                    'contenido' => $mensaje->mensaje,
                    'fecha' => $mensaje->fecha,
                    'id_depaR' => $mensaje->id_depaR,
                    'id_depaD' => $mensaje->id_depaD,
                ]
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Error al enviar mensaje: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Error al enviar mensaje'
            ], 500);
        }
    }

    /**
     * Debug endpoint - Check if messages exist in MongoDB
     */
    public function debugMessages(): JsonResponse
    {
        try {
            $total = Mensaje::count();
            $allMessages = Mensaje::orderBy('fecha', 'asc')->get();
            
            return response()->json([
                'total_messages' => $total,
                'messages' => $allMessages->map(function ($msg) {
                    return [
                        '_id' => (string)$msg->_id,
                        'remitente' => $msg->remitente,
                        'destinatario' => $msg->destinatario,
                        'mensaje' => $msg->mensaje,
                        'fecha' => $msg->fecha,
                        'tipo' => $msg->tipo,
                        'id_depaR' => $msg->id_depaR,
                        'id_depaD' => $msg->id_depaD,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Debug endpoint - Clear all test messages
     */
    public function clearDebugMessages(): JsonResponse
    {
        try {
            $deleted = Mensaje::delete();
            return response()->json([
                'deleted' => $deleted,
                'message' => 'All messages deleted'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get messages for a departamento
     */
    public function getMessages(Request $request): JsonResponse
    {
        try {
            $id_depa = (int)$request->query('id_depa');
            $contacto_id = (int)$request->query('contacto_id');
            $usuario_id = (int)($request->query('usuario_id') ?? 999);
            $page = (int)($request->query('page') ?? 1);
            $per_page = (int)($request->query('per_page') ?? 50);
            $skip = ($page - 1) * $per_page;

            $query = Mensaje::query();

            if ($contacto_id) {
                $query->where(function ($q) use ($usuario_id, $contacto_id) {
                    $q->where(function ($subQ) use ($usuario_id, $contacto_id) {
                        $subQ->where('remitente', $usuario_id)
                             ->where('destinatario', $contacto_id);
                    })
                    ->orWhere(function ($subQ) use ($usuario_id, $contacto_id) {
                        $subQ->where('remitente', $contacto_id)
                             ->where('destinatario', $usuario_id);
                    });
                });
            } elseif ($id_depa) {
                $query->where(function ($q) use ($id_depa) {
                    $q->where('id_depaR', $id_depa)
                      ->orWhere('id_depaD', $id_depa);
                });
            }

            $total = $query->count();
            
            $resultado = $query->orderBy('fecha', 'asc')
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

            return response()->json($resultado);
        } catch (\Exception $e) {
            \Log::error('Error en getMessages: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
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
