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
            'destinatario_id' => 'nullable|integer',
            'id_depa' => 'required|integer',
            'contenido' => 'required|string|max:1000',
            'tipo' => 'required|in:personal,departamento,general',
        ]);

        $mensaje = Mensaje::create([
            'remitente_id' => $validated['remitente_id'],
            'destinatario_id' => $validated['destinatario_id'],
            'id_depa' => $validated['id_depa'],
            'contenido' => $validated['contenido'],
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
                'remitente_id' => $mensaje->remitente_id,
                'contenido' => $mensaje->contenido,
                'fecha' => $mensaje->fecha,
            ]
        ], 201);
    }

    /**
     * Get messages for a departamento
     */
    public function getMessages(Request $request, $id_depa): JsonResponse
    {
        $validated = $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
            'usuario_id' => 'nullable|integer',
        ]);

        $page = $validated['page'] ?? 1;
        $per_page = $validated['per_page'] ?? 20;
        $skip = ($page - 1) * $per_page;

        $query = Mensaje::where('id_depa', $id_depa)
            ->orderBy('fecha', 'desc');

        // If usuario_id provided, filter personal messages
        if (isset($validated['usuario_id'])) {
            $query->where(function ($q) use ($validated) {
                $q->where('remitente_id', $validated['usuario_id'])
                  ->orWhere('destinatario_id', $validated['usuario_id']);
            });
        }

        $total = $query->count();
        $mensajes = $query->skip($skip)
            ->take($per_page)
            ->get()
            ->map(function ($mensaje) {
                return [
                    'id' => (string)$mensaje->_id,
                    'remitente_id' => $mensaje->remitente_id,
                    'destinatario_id' => $mensaje->destinatario_id,
                    'contenido' => $mensaje->contenido,
                    'tipo' => $mensaje->tipo,
                    'id_depa' => $mensaje->id_depa,
                    'leido' => $mensaje->leido,
                    'fecha' => $mensaje->fecha,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $mensajes,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page,
                'pages' => ceil($total / $per_page),
            ]
        ]);
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
        $validated = $request->validate([
            'usuario_id' => 'required|integer',
            'id_depa' => 'required|integer',
            'nombre_usuario' => 'required|string',
        ]);

        // Broadcast the typing event
        UsuarioEscribiendo::dispatch(
            $validated['usuario_id'],
            $validated['id_depa'],
            $validated['nombre_usuario']
        );

        return response()->json([
            'success' => true,
            'message' => 'Typing indicator enviado'
        ]);
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
