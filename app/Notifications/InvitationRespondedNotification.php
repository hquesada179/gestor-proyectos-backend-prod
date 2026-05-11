<?php

namespace App\Notifications;

use App\Models\ProjectInvitation;
use App\Models\User;
use Illuminate\Notifications\Notification;

class InvitationRespondedNotification extends Notification
{
    public function __construct(
        private readonly ProjectInvitation $invitation,
        private readonly string $response,
        private readonly User $respondent,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $projectName = $this->invitation->proyecto?->nombre ?? 'Proyecto';
        $action      = $this->response === 'accepted' ? 'aceptó' : 'rechazó';

        return [
            'type'       => 'invitation_' . $this->response,
            'title'      => 'Invitación ' . ($this->response === 'accepted' ? 'aceptada' : 'rechazada'),
            'body'       => "{$this->respondent->name} {$action} la invitación al proyecto {$projectName}",
            'project_id' => $this->invitation->proyecto_id,
        ];
    }
}
