<?php

namespace App\Filament\Resources\BugTracker;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;
use Filament\Forms\Components\ColorPicker;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\BugTracker\TagResource\Pages\ListTags;
use App\Filament\Resources\BugTracker\TagResource\Pages\CreateTag;
use App\Filament\Resources\BugTracker\TagResource\Pages\EditTag;
use App\Filament\Resources\BugTracker\TagResource\Pages;
use App\Models\BugTracker\Tag;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-hashtag';
    protected static string | \UnitEnum | null $navigationGroup = 'Bug Tracker';
    protected static ?string $navigationLabel = 'Tags';
    protected static ?int $navigationSort = 30;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(50)->live(onBlur: true)
                ->afterStateUpdated(fn ($state, $set, $context) =>
                    $context === 'create' ? $set('slug', Str::slug($state)) : null),
            TextInput::make('slug')->required()->maxLength(50)->unique(ignoreRecord: true),
            ColorPicker::make('color')->default('#94a3b8'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('color'),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->color('gray')->size('sm'),
                TextColumn::make('tasks_count')->counts('tasks')->badge(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListTags::route('/'),
            'create' => CreateTag::route('/create'),
            'edit'   => EditTag::route('/{record}/edit'),
        ];
    }
}
