<?php

namespace App\Notifications;

use App\Models\File;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FileMilestone extends Notification
{
    use Queueable;

    public function __construct(public File $file, public int $milestone) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'file_milestone',
            'file_id' => $this->file->id,
            'file_title' => $this->file->title,
            'milestone' => $this->milestone,
            'message' => "\"{$this->file->title}\" hat {$this->milestone} Downloads erreicht!",
            'url' => route('files.show', $this->file),
        ];
    }
}
