<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class AccountReactivatedByAdmin extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly User $admin
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'type' => 'account_reactivated',
            'title_key' => 'Tu cuenta ha sido reactivada',
            'body_key' => 'Tu cuenta ha sido reactivada por el administrador :admin_name.',
            'params' => [
                'admin_name' => $this->admin->first_name . ' ' . $this->admin->last_name,
            ],
            'action_url' => null,
            'entity_type' => 'user',
            'entity_id' => $notifiable->id,
        ]);
    }
}
