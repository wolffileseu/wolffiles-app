<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TutorialCategoryResource\Pages;
use App\Models\TutorialCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TutorialCategoryResource extends Resource
{
    protected static ?string $model = TutorialCategory::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationGroup = 'Wiki & Tutorials';
    protected static ?string $navigationLabel = 'Tutorial Categories';
    protected static ?int $navigationSort = 4;


    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can('view_tutorial_categories');
    }



    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\TextInput::make('slug')->unique(ignoreRecord: true),
            Forms\Components\Textarea::make('description')->rows(2),
            Forms\Components\TextInput::make('icon'),
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
                Tables\Columns\TextColumn::make('tutorials_count')->counts('tutorials')->label('Tutorials'),
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
            'index' => Pages\ListTutorialCategories::route('/'),
            'create' => Pages\CreateTutorialCategory::route('/create'),
            'edit' => Pages\EditTutorialCategory::route('/{record}/edit'),
        ];
    }
}
