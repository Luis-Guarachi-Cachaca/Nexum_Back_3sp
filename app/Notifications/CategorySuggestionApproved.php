<?php

namespace App\Notifications;

use App\Models\CategorySuggestion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class CategorySuggestionApproved extends Notification implements ShouldQueue
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
            'type' => 'category_suggestion_approved',
            'title_key' => 'Tu sugerencia de categoría fue aprobada',
            'body_key' => 'La categoría :category_name ha sido aprobada y asignada a tu proyecto :project_name.',
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
