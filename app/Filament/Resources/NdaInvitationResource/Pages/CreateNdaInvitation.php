<?php

namespace App\Filament\Resources\NdaInvitationResource\Pages;

use App\Filament\Resources\NdaInvitationResource;
use App\Models\NdaInvitation;
use Filament\Resources\Pages\CreateRecord;

class CreateNdaInvitation extends CreateRecord
{
    protected static string $resource = NdaInvitationResource::class;

    protected ?string $plainToken = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->plainToken = NdaInvitation::generateToken();

        $data['token_hash'] = NdaInvitation::hashToken($this->plainToken);
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->plainToken !== null) {
            NdaInvitationResource::notifyLink($this->plainToken);
            $this->plainToken = null;
        }
    }

    protected function getCreatedNotification(): ?\Filament\Notifications\Notification
    {
        return null;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
