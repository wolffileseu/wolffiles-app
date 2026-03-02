<?php

namespace App\Notifications;

use App\Models\File;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DownloadMilestone extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public File $file,
        public int $milestone
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("🎉 {$this->milestone} downloads for \"{$this->file->title}\"!")
            ->greeting("Congratulations {$notifiable->name}!")
            ->line("Your file \"{$this->file->title}\" has reached **{$this->milestone} downloads**!")
            ->action('View File', route('files.show', $this->file))
            ->line('Thank you for contributing to Wolffiles.eu!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'file_id' => $this->file->id,
            'file_title' => $this->file->title,
            'milestone' => $this->milestone,
            'url' => route('files.show', $this->file),
        ];
    }
}
