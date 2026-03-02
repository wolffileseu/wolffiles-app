<?php

namespace App\Filament\Resources\SocialMediaChannelResource\Pages;

use App\Filament\Resources\SocialMediaChannelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSocialMediaChannel extends EditRecord
{
    protected static string $resource = SocialMediaChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
