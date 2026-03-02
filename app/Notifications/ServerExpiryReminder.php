<?php

namespace App\Notifications;

use App\Models\ServerOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServerExpiryReminder extends Notification
{
    use Queueable;

    public function __construct(
        protected ServerOrder $order,
        protected int $daysRemaining
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $urgency = match(true) {
            $this->daysRemaining <= 1 => '⚠️ MORGEN',
            $this->daysRemaining <= 3 => '⚠️ In 3 Tagen',
            default => 'In 7 Tagen',
        };

        return (new MailMessage)
            ->subject("{$urgency} läuft dein Server ab: {$this->order->server_name}")
            ->greeting("Hallo {$notifiable->name}!")
            ->line("Dein Gameserver **{$this->order->server_name}** ({$this->order->slots} Slots) läuft in **{$this->daysRemaining} Tag(en)** ab.")
            ->line("Ablaufdatum: **{$this->order->paid_until->format('d.m.Y H:i')}**")
            ->line("Nach Ablauf wird der Server pausiert. Deine Daten bleiben 30 Tage erhalten.")
            ->action('Jetzt verlängern', route('hosting.renew', $this->order))
            ->line('Vielen Dank, dass du Wolffiles.eu nutzt!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'server_expiry_reminder',
            'order_id' => $this->order->id,
            'server_name' => $this->order->server_name,
            'days_remaining' => $this->daysRemaining,
            'paid_until' => $this->order->paid_until->toDateString(),
            'message' => "Server \"{$this->order->server_name}\" läuft in {$this->daysRemaining} Tag(en) ab.",
        ];
    }
}
