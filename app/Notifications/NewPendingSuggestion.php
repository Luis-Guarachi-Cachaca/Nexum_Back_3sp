<?php

namespace App\Notifications;

use App\Models\SkillSuggestion;
use App\Models\CategorySuggestion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class NewPendingSuggestion extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $suggestionType,
        public readonly SkillSuggestion|CategorySuggestion $suggestion
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): DatabaseMessage
    {
        $isSkill = $this->suggestionType === 'skill';
        $name = $isSkill ? $this->suggestion->name : $this->suggestion->name;
        $userName = $this->suggestion->user->first_name . ' ' . $this->suggestion->user->last_name;

        return new DatabaseMessage([
            'type' => 'new_pending_suggestion',
            'title_key' => 'Nueva sugerencia pendiente',
            'body_key' => ':user_name ha sugerido :suggestion_type :suggestion_name que requiere aprobación.',
            'params' => [
                'suggestion_type' => $isSkill ? 'una habilidad' : 'una categoría',
                'suggestion_name' => $name,
                'user_name' => $userName,
            ],
            'action_url' => $isSkill ? '/admin/skill-suggestions' : '/admin/category-suggestions',
            'entity_type' => $isSkill ? 'skill_suggestion' : 'category_suggestion',
            'entity_id' => $this->suggestion->id,
        ]);
    }
}
