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
use App\Filament\Resources\TutorialCategoryResource\Pages\ListTutorialCategories;
use App\Filament\Resources\TutorialCategoryResource\Pages\CreateTutorialCategory;
use App\Filament\Resources\TutorialCategoryResource\Pages\EditTutorialCategory;
use App\Filament\Resources\TutorialCategoryResource\Pages;
use App\Models\TutorialCategory;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TutorialCategoryResource extends Resource
{
    protected static ?string $model = TutorialCategory::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-folder';
    protected static string | \UnitEnum | null $navigationGroup = 'Wiki & Tutorials';
    protected static ?string $navigationLabel = 'Tutorial Categories';
    protected static ?int $navigationSort = 4;





    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('slug')->unique(ignoreRecord: true),
            Textarea::make('description')->rows(2),
            TextInput::make('icon'),
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
                TextColumn::make('tutorials_count')->counts('tutorials')->label('Tutorials'),
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
            'index' => ListTutorialCategories::route('/'),
            'create' => CreateTutorialCategory::route('/create'),
            'edit' => EditTutorialCategory::route('/{record}/edit'),
        ];
    }
}
