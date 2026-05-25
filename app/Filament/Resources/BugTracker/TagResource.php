<?php

namespace App\Filament\Resources\BugTracker;

use App\Filament\Resources\BugTracker\TagResource\Pages;
use App\Models\BugTracker\Tag;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;
    protected static ?string $navigationIcon = 'heroicon-o-hashtag';
    protected static ?string $navigationGroup = 'Bug Tracker';
    protected static ?string $navigationLabel = 'Tags';
    protected static ?int $navigationSort = 30;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(50)->live(onBlur: true)
                ->afterStateUpdated(fn ($state, $set, $context) =>
                    $context === 'create' ? $set('slug', \Illuminate\Support\Str::slug($state)) : null),
            Forms\Components\TextInput::make('slug')->required()->maxLength(50)->unique(ignoreRecord: true),
            Forms\Components\ColorPicker::make('color')->default('#94a3b8'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('color'),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->color('gray')->size('sm'),
                Tables\Columns\TextColumn::make('tasks_count')->counts('tasks')->badge(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTags::route('/'),
            'create' => Pages\CreateTag::route('/create'),
            'edit'   => Pages\EditTag::route('/{record}/edit'),
        ];
    }
}
