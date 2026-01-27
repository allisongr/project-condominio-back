<?php

use Illuminate\Support\Facades\Broadcast;

// Broadcast channel authorization
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Chat channels - for personal messages
Broadcast::channel('chat.{remitente}.{destinatario}', function ($user, $remitente, $destinatario) {
    \Log::info('Autorizando canal de chat', [
        'user_id' => $user->id ?? 'null',
        'remitente' => $remitente,
        'destinatario' => $destinatario,
    ]);
    
    $authorized = (int) $user->id === (int) $remitente || (int) $user->id === (int) $destinatario;
    
    \Log::info('Resultado de autorización de canal', [
        'authorized' => $authorized,
        'user_id' => $user->id,
        'comparison' => [
            'user_equals_remitente' => (int) $user->id === (int) $remitente,
            'user_equals_destinatario' => (int) $user->id === (int) $destinatario,
        ]
    ]);
    
    return $authorized;
});

// Typing indicator channels
Broadcast::private('typing.{usuario_id}.{destinatario_id}', function ($user, $usuario_id, $destinatario_id) {
    return (int) $user->id === (int) $usuario_id || (int) $user->id === (int) $destinatario_id;
});

// Departamento chat channels
Broadcast::private('chat.departamento.{id_depa}', function ($user, $id_depa) {
    // Check if user belongs to this departamento
    return true; // TODO: Verify user belongs to departamento
});