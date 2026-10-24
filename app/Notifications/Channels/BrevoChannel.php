<?php

namespace App\Notifications\Channels;

use App\Notifications\ResetPasswordBrevoNotification;
use Illuminate\Notifications\Notification;

class BrevoChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notification instanceof ResetPasswordBrevoNotification) {
            throw new \InvalidArgumentException('Tipo de notificación no compatible.');
        }

        $notification->send($notifiable);
    }
}
