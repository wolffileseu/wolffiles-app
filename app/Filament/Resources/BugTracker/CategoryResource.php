<?php

namespace App\Filament\Resources\BugTracker;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\BugTracker\CategoryResource\Pages\ListCategories;
use App\Filament\Resources\BugTracker\CategoryResource\Pages\CreateCategory;
use App\Filament\Resources\BugTracker\CategoryResource\Pages\EditCategory;
use App\Filament\Resources\BugTracker\CategoryResource\Pages;
use App\Models\BugTracker\Category;
use App\Models\BugTracker\Project;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-tag';
    protected static string | \UnitEnum | null $navigationGroup = 'Bug Tracker';
    protected static ?string $navigationLabel = 'Categories';
    protected static ?int $navigationSort = 20;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('project_id')
                ->label('Project')
                ->options(fn () => Project::where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'))
                ->required()->searchable(),
            TextInput::make('name')->required()->maxLength(120)->live(onBlur: true)
                ->afterStateUpdated(fn ($state, $set, $context) =>
                    $context === 'create' ? $set('slug', Str::slug($state)) : null),
            TextInput::make('slug')->required()->maxLength(60),
            Textarea::make('description')->rows(2)->columnSpanFull(),
            TextInput::make('sort_order')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('project_id')
            ->columns([
                TextColumn::make('project.name')->sortable()
                    ->badge()->color(fn ($record) => $record->project?->color ? null : 'gray'),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->color('gray')->size('sm'),
                TextColumn::make('tasks_count')->counts('tasks')->label('Tasks')->badge(),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->filters([
                SelectFilter::make('project_id')->label('Project')
                    ->options(fn () => Project::orderBy('sort_order')->pluck('name', 'id')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit'   => EditCategory::route('/{record}/edit'),
        ];
    }
}
