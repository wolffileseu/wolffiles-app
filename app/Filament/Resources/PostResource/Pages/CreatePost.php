<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use App\Services\SocialMedia\SocialMediaService;
use App\Services\TelegramNotificationService;
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    protected function afterCreate(): void
    {
        $post = $this->record;

        // Broadcast if published immediately
        if ($post->is_published) {
            app(SocialMediaService::class)->broadcastNewsPosted($post);
            app(TelegramNotificationService::class)->notifyNewsPosted($post);
        }
    }
}