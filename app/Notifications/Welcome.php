<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Welcome extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to Wolffiles.eu!')
            ->greeting("Welcome {$notifiable->name}!")
            ->line('Thank you for joining Wolffiles.eu — the community hub for Wolfenstein: Enemy Territory!')
            ->line('Here\'s what you can do:')
            ->line('• Browse and download thousands of maps, mods & scripts')
            ->line('• Upload your own creations to share with the community')
            ->line('• Rate and comment on files')
            ->line('• Preview maps in our interactive 3D viewer')
            ->action('Start Exploring', route('home'))
            ->line('See you on the battlefield!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Welcome to Wolffiles.eu!',
            'url' => route('home'),
        ];
    }
}
