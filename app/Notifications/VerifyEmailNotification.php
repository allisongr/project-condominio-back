<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $token;
    protected $frontendUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct($token, $frontendUrl = null)
    {
        $this->token = $token;
        $this->frontendUrl = $frontendUrl ?? config('app.frontend_url', 'http://localhost:5173');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = $this->frontendUrl . '/verify-email?token=' . $this->token;

        return (new MailMessage)
            ->subject('Verificar correo electrónico')
            ->greeting('¡Hola ' . $notifiable->persona->nombre . '!')
            ->line('Has sido registrado en el sistema de administración del condominio.')
            ->line('Por favor verifica tu correo electrónico haciendo clic en el botón de abajo.')
            ->action('Verificar Email', $verificationUrl)
            ->line('Si no solicitaste este registro, puedes ignorar este correo.')
            ->salutation('Saludos, ' . config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'token' => $this->token,
        ];
    }
}
