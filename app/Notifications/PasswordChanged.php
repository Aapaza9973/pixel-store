<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChanged extends Notification
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu contraseña fue actualizada')
            ->greeting('¡Hola!')
            ->line('La contraseña de tu cuenta en Pixel Store fue modificada.')
            ->line('Si no fuiste tú, contacta al administrador inmediatamente.')
            ->action('Iniciar sesión', url('/login'));
    }
}
