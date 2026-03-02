<?php

namespace App\Notifications;

use App\Models\File;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewFileUploaded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public File $file,
        public User $uploader
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New file uploaded: \"{$this->file->title}\"")
            ->greeting('New upload awaiting review!')
            ->line("**{$this->uploader->name}** uploaded a new file:")
            ->line("Title: {$this->file->title}")
            ->line("Category: " . ($this->file->category->name ?? 'Unknown'))
            ->line("Size: " . number_format($this->file->file_size / 1024 / 1024, 1) . ' MB')
            ->action('Review in Admin', url('/admin/files/' . $this->file->slug . '/edit'))
            ->line('Please review and approve or reject this file.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'file_id' => $this->file->id,
            'file_title' => $this->file->title,
            'uploader_name' => $this->uploader->name,
            'url' => url('/admin/files/' . $this->file->slug . '/edit'),
        ];
    }
}
