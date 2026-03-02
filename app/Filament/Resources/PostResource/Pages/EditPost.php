<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use App\Services\SocialMedia\SocialMediaService;
use App\Services\TelegramNotificationService;
use Filament\Resources\Pages\EditRecord;

/** @method \App\Models\Post getRecord() */
class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected function beforeSave(): void
    {
        // Track if this is a new publish (was unpublished, now publishing)
        $this->wasPublished = $this->record->is_published;
    }

    protected function afterSave(): void
    {
        $post = $this->record;

        // Only broadcast if the post was just published (not on every edit)
        if ($post->is_published && !($this->wasPublished ?? false)) {
            app(SocialMediaService::class)->broadcastNewsPosted($post);
            app(TelegramNotificationService::class)->notifyNewsPosted($post);
        }
    }

    private bool $wasPublished = false;
}