<?php

namespace App\Notifications;

use App\Models\CategorySuggestion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class CategorySuggestionRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly CategorySuggestion $suggestion
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'type' => 'category_suggestion_rejected',
            'title_key' => 'Tu sugerencia de categoría fue rechazada',
            'body_key' => 'La categoría :category_name para el proyecto :project_name no fue aprobada por el administrador.',
            'params' => [
                'category_name' => $this->suggestion->name,
                'project_name' => $this->suggestion->project?->title ?? 'N/A',
            ],
            'action_url' => "/projects/{$this->suggestion->project_id}",
            'entity_type' => 'category_suggestion',
            'entity_id' => $this->suggestion->id,
        ]);
    }
}
