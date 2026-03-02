<?php
namespace App\Filament\Resources\WikiArticleResource\Pages;
use App\Filament\Resources\WikiArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditWikiArticle extends EditRecord
{
    protected static string $resource = WikiArticleResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
