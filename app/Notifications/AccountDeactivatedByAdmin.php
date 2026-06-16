<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;

class AccountDeactivatedByAdmin extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly User $admin
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase($notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'type' => 'account_deactivated',
            'title_key' => 'Tu cuenta ha sido desactivada',
            'body_key' => 'Tu cuenta ha sido desactivada por el administrador :admin_name.',
            'params' => [
                'admin_name' => $this->admin->first_name . ' ' . $this->admin->last_name,
            ],
            'action_url' => null,
            'entity_type' => 'user',
            'entity_id' => $notifiable->id,
        ]);
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu cuenta Nexum ha sido desactivada')
            ->greeting('Hola ' . $notifiable->first_name . ',')
            ->line('Te informamos que tu cuenta Nexum ha sido desactivada por el administrador.')
            ->line('El administrador ' . $this->admin->first_name . ' ' . $this->admin->last_name . ' ha tomado esta decisión.')
            ->line('Si tienes alguna pregunta, por favor contacta al soporte.')
            ->salutation('Saludos, el equipo de Nexum');
    }
}
