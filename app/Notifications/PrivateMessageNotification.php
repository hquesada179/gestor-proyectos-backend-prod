<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class PrivateMessageNotification extends Notification
{
    public function __construct(
        private readonly string $senderName,
        private readonly int $conversationId,
        private readonly int $projectId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'            => 'private_message',
            'title'           => 'Nuevo mensaje privado',
            'body'            => "{$this->senderName} te envió un mensaje",
            'conversation_id' => $this->conversationId,
            'project_id'      => $this->projectId,
        ];
    }
}
