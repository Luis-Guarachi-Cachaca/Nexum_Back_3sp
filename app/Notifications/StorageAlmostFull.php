<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class StorageAlmostFull extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $storageUsed,
        public readonly int $storageLimit
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): DatabaseMessage
    {
        $percentage = round(($this->storageUsed / $this->storageLimit) * 100);

        return new DatabaseMessage([
            'type' => 'storage_almost_full',
            'title_key' => 'Almacenamiento casi lleno',
            'body_key' => 'Tu almacenamiento está al :percentage% de su capacidad. Considera eliminar archivos antiguos.',
            'params' => [
                'percentage' => $percentage,
                'storage_used' => $this->storageUsed,
                'storage_limit' => $this->storageLimit,
            ],
            'action_url' => '/portfolio',
            'entity_type' => 'user',
            'entity_id' => $notifiable->id,
        ]);
    }
}
