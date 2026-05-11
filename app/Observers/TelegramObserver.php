<?php

namespace App\Observers;

use App\Models\Comment;
use App\Models\User;
use App\Models\File;
use App\Services\TelegramNotificationService;

class TelegramObserver
{
    /**
     * Register observers in AppServiceProvider::boot()
     *
     * Comment::observe(TelegramObserver::class);
     * User::observe(TelegramObserver::class);
     * File::observe(TelegramObserver::class);
     */

    public function created($model): void
    {
        $telegram = app(TelegramNotificationService::class);

        if ($model instanceof Comment) {
            $telegram->notifyCommentPosted($model);
        }

        if ($model instanceof User) {
            $telegram->notifyUserRegistered($model);
        }

        // Notify on every new File regardless of status:
        // - pending uploads from untrusted users
        // - direct-approved uploads from trusted users
        if ($model instanceof File && in_array($model->status, ['pending', 'approved'], true)) {
            $telegram->notifyFileUploaded($model);
        }
    }

    /**
     * Catches the case where a pending File gets approved later by an admin.
     * Without this, files that started as pending and were approved afterwards
     * would never trigger a notification.
     */
    public function updated($model): void
    {
        if (! $model instanceof File) return;
        if (! $model->wasChanged('status')) return;

        // Only notify on the transition pending -> approved
        $previous = $model->getOriginal('status');
        if ($previous === 'pending' && $model->status === 'approved') {
            app(TelegramNotificationService::class)->notifyFileUploaded($model);
        }
    }
}
