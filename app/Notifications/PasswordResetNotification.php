<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $resetCode,
        public string $personName
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Código de Recuperación de Contraseña - Condominio Chat')
            ->greeting('¡Hola ' . $this->personName . '!')
            ->line('Has solicitado recuperar tu contraseña.')
            ->line('Tu código de recuperación es:')
            ->line('')
            ->line('**' . $this->resetCode . '**')
            ->line('')
            ->line('Este código expirará en 15 minutos.')
            ->line('Si no solicitaste esta recuperación, ignora este correo.')
            ->action('Ir a recuperar contraseña', config('app.frontend_url') . '/reset-password')
            ->salutation('Equipo de Condominio Chat');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'reset_code' => $this->resetCode,
        ];
    }
}
