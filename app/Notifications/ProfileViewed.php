<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class ProfileViewed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ?User $visitor,
        public readonly string $visitorName
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): DatabaseMessage
    {
        $visitorPortfolioId = $this->visitor?->portfolio?->id;
        $actionUrl = $visitorPortfolioId ? "/portfolio/{$visitorPortfolioId}" : '/visitantes';

        return new DatabaseMessage([
            'type' => 'profile_viewed',
            'title_key' => 'Alguien vio tu perfil',
            'body_key' => ':visitor_name ha visitado tu perfil.',
            'params' => [
                'visitor_name' => $this->visitorName,
            ],
            'action_url' => $actionUrl,
            'entity_type' => 'profile_visit',
            'entity_id' => $notifiable->portfolio?->id,
        ]);
    }
}
