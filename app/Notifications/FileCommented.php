<?php

namespace App\Notifications;

use App\Models\File;
use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FileCommented extends Notification
{
    use Queueable;

    public function __construct(public File $file, public Comment $comment) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'file_commented',
            'file_id' => $this->file->id,
            'file_title' => $this->file->title,
            'comment_id' => $this->comment->id,
            'commenter_name' => $this->comment->user->name ?? 'Guest',
            'comment_excerpt' => \Illuminate\Support\Str::limit($this->comment->body, 100),
            'url' => route('files.show', $this->file),
        ];
    }
}
