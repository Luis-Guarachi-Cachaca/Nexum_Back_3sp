<?php

namespace App\Notifications;

use App\Models\SkillSuggestion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class SkillSuggestionRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly SkillSuggestion $suggestion
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'type' => 'skill_suggestion_rejected',
            'title_key' => 'Tu sugerencia de habilidad fue rechazada',
            'body_key' => 'La habilidad :skill_name no fue aprobada por el administrador.',
            'params' => [
                'skill_name' => $this->suggestion->name,
            ],
            'action_url' => '/portfolio/skills',
            'entity_type' => 'skill_suggestion',
            'entity_id' => $this->suggestion->id,
        ]);
    }
}
