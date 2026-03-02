<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewComment extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Comment $comment,
        public string $commentableTitle,
        public string $commentableUrl
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New comment on \"{$this->commentableTitle}\"")
            ->greeting("Hi {$notifiable->name}!")
            ->line("{$this->comment->user->name} commented on \"{$this->commentableTitle}\":")
            ->line("\"{$this->comment->body}\"")
            ->action('View', $this->commentableUrl)
            ->line('Thank you for being part of Wolffiles.eu!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'comment_id' => $this->comment->id,
            'commenter_name' => $this->comment->user->name,
            'commentable_title' => $this->commentableTitle,
            'body' => \Illuminate\Support\Str::limit($this->comment->body, 100),
            'url' => $this->commentableUrl,
        ];
    }
}
