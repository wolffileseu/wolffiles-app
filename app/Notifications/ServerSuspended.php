<?php

namespace App\Notifications;

use App\Models\ServerOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServerSuspended extends Notification
{
    use Queueable;

    public function __construct(protected ServerOrder $order) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("⏸️ Server suspendiert: {$this->order->server_name}")
            ->greeting("Hallo {$notifiable->name}!")
            ->line("Dein Gameserver **{$this->order->server_name}** wurde suspendiert, da die Laufzeit abgelaufen ist.")
            ->line("Deine Server-Daten bleiben noch **30 Tage** erhalten. Danach werden sie unwiderruflich gelöscht.")
            ->action('Jetzt verlängern & reaktivieren', route('hosting.renew', $this->order))
            ->line('Bei Fragen wende dich an uns über Discord.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'server_suspended',
            'order_id' => $this->order->id,
            'server_name' => $this->order->server_name,
            'message' => "Server \"{$this->order->server_name}\" wurde suspendiert.",
        ];
    }
}
