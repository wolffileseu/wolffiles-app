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

        if ($model instanceof File && $model->status === 'pending') {
            $telegram->notifyFileUploaded($model);
        }
    }
}