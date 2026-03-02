<?php

namespace App\Notifications;

use App\Models\File;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FileStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public File $file,
        public string $status,
        public ?string $reason = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Your file \"{$this->file->title}\" has been {$this->status}");

        if ($this->status === 'approved') {
            $mail->greeting('Good news!')
                ->line("Your file \"{$this->file->title}\" has been approved and is now live.")
                ->action('View File', route('files.show', $this->file));
        } else {
            $mail->greeting('File Review Update')
                ->line("Your file \"{$this->file->title}\" has been rejected.")
                ->line("Reason: {$this->reason}")
                ->line('You can upload a corrected version anytime.');
        }

        return $mail->line('Thank you for contributing to Wolffiles.eu!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'file_id' => $this->file->id,
            'file_title' => $this->file->title,
            'status' => $this->status,
            'reason' => $this->reason,
        ];
    }
}
