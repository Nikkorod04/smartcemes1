<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * SmartCEMES system notification (5.14): Laravel database notification
 * wired to the top-header bell. Icon/color follow the design-system map.
 * Optional dedup_key powers the 7-day de-duplication of scheduled reminders.
 */
class SmartCemesNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $body,
        public string $icon = 'doc',
        public string $color = 'blue',
        public ?string $dedupKey = null,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'icon' => $this->icon,
            'color' => $this->color,
            'dedup_key' => $this->dedupKey,
        ];
    }
}
