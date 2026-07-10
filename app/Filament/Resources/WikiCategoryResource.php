<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\WikiCategoryResource\Pages\ListWikiCategories;
use App\Filament\Resources\WikiCategoryResource\Pages\CreateWikiCategory;
use App\Filament\Resources\WikiCategoryResource\Pages\EditWikiCategory;
use App\Filament\Resources\WikiCategoryResource\Pages;
use App\Models\WikiCategory;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WikiCategoryResource extends Resource
{
    protected static ?string $model = WikiCategory::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-folder';
    protected static string | \UnitEnum | null $navigationGroup = 'Wiki & Tutorials';
    protected static ?string $navigationLabel = 'Wiki Categories';
    protected static ?int $navigationSort = 3;





    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('slug')->unique(ignoreRecord: true),
            Textarea::make('description')->rows(2),
            TextInput::make('icon')->hint('Heroicon name, e.g. heroicon-o-book-open'),
            TextInput::make('sort_order')->numeric()->default(0),
            Toggle::make('is_active')->default(true),
            KeyValue::make('name_translations'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug'),
                TextColumn::make('articles_count')->counts('articles')->label('Articles'),
                TextColumn::make('sort_order')->sortable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWikiCategories::route('/'),
            'create' => CreateWikiCategory::route('/create'),
            'edit' => EditWikiCategory::route('/{record}/edit'),
        ];
    }
}
