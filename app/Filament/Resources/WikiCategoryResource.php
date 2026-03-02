<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WikiCategoryResource\Pages;
use App\Models\WikiCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WikiCategoryResource extends Resource
{
    protected static ?string $model = WikiCategory::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationGroup = 'Wiki & Tutorials';
    protected static ?string $navigationLabel = 'Wiki Categories';
    protected static ?int $navigationSort = 3;


    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can('view_wiki_categories');
    }



    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\TextInput::make('slug')->unique(ignoreRecord: true),
            Forms\Components\Textarea::make('description')->rows(2),
            Forms\Components\TextInput::make('icon')->hint('Heroicon name, e.g. heroicon-o-book-open'),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
            Forms\Components\Toggle::make('is_active')->default(true),
            Forms\Components\KeyValue::make('name_translations'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug'),
                Tables\Columns\TextColumn::make('articles_count')->counts('articles')->label('Articles'),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWikiCategories::route('/'),
            'create' => Pages\CreateWikiCategory::route('/create'),
            'edit' => Pages\EditWikiCategory::route('/{record}/edit'),
        ];
    }
}
