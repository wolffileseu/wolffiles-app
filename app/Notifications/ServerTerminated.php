<?php

namespace App\Notifications;

use App\Models\ServerOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServerTerminated extends Notification
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
            ->subject("❌ Server gelöscht: {$this->order->server_name}")
            ->greeting("Hallo {$notifiable->name}!")
            ->line("Dein Gameserver **{$this->order->server_name}** wurde nach 30 Tagen Inaktivität endgültig gelöscht.")
            ->line("Alle Daten wurden entfernt. Falls du einen neuen Server möchtest, kannst du jederzeit einen neuen erstellen.")
            ->action('Neuen Server mieten', route('hosting.index'));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'server_terminated',
            'order_id' => $this->order->id,
            'server_name' => $this->order->server_name,
            'message' => "Server \"{$this->order->server_name}\" wurde endgültig gelöscht.",
        ];
    }
}
