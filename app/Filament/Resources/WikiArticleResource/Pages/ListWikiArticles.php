<?php
namespace App\Filament\Resources\WikiArticleResource\Pages;
use Filament\Actions\CreateAction;
use App\Filament\Resources\WikiArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListWikiArticles extends ListRecords
{
    protected static string $resource = WikiArticleResource::class;
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
