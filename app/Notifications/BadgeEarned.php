<?php

namespace App\Notifications;

use App\Models\Badge;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BadgeEarned extends Notification
{
    use Queueable;

    public function __construct(public Badge $badge) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'badge_earned',
            'badge_id' => $this->badge->id,
            'badge_name' => $this->badge->name,
            'badge_icon' => $this->badge->icon,
            'message' => "Du hast das Abzeichen \"{$this->badge->name}\" verdient! {$this->badge->icon}",
        ];
    }
}
