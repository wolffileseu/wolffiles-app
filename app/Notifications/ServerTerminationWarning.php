<?php

namespace App\Notifications;

use App\Models\ServerOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServerTerminationWarning extends Notification
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
        return (new MailMessage)
            ->subject("🚨 Letzte Warnung: Server wird in {$this->daysRemaining} Tagen gelöscht!")
            ->greeting("Hallo {$notifiable->name}!")
            ->line("**LETZTE WARNUNG**: Dein suspendierter Server **{$this->order->server_name}** wird in **{$this->daysRemaining} Tag(en)** endgültig gelöscht!")
            ->line("Alle Daten (Configs, Maps, Backups) gehen unwiderruflich verloren.")
            ->action('JETZT verlängern & retten', route('hosting.renew', $this->order))
            ->line('Dies ist die letzte Erinnerung.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'server_termination_warning',
            'order_id' => $this->order->id,
            'server_name' => $this->order->server_name,
            'days_remaining' => $this->daysRemaining,
            'message' => "LETZTE WARNUNG: Server \"{$this->order->server_name}\" wird in {$this->daysRemaining} Tagen gelöscht!",
        ];
    }
}
