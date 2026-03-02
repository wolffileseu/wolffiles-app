<?php

namespace App\Filament\Resources\LuaScriptResource\Pages;

use App\Filament\Resources\LuaScriptResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLuaScript extends CreateRecord
{
    protected static string $resource = LuaScriptResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }
}
