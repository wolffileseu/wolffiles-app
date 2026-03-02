<?php
namespace App\Filament\Resources\WikiArticleResource\Pages;
use App\Filament\Resources\WikiArticleResource;
use Filament\Resources\Pages\CreateRecord;
class CreateWikiArticle extends CreateRecord
{
    protected static string $resource = WikiArticleResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }
}
