<?php

namespace App\Filament\Resources\BugTracker;

use App\Filament\Resources\BugTracker\CategoryResource\Pages;
use App\Models\BugTracker\Category;
use App\Models\BugTracker\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Bug Tracker';
    protected static ?string $navigationLabel = 'Categories';
    protected static ?int $navigationSort = 20;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('project_id')
                ->label('Project')
                ->options(fn () => Project::where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'))
                ->required()->searchable(),
            Forms\Components\TextInput::make('name')->required()->maxLength(120)->live(onBlur: true)
                ->afterStateUpdated(fn ($state, $set, $context) =>
                    $context === 'create' ? $set('slug', \Illuminate\Support\Str::slug($state)) : null),
            Forms\Components\TextInput::make('slug')->required()->maxLength(60),
            Forms\Components\Textarea::make('description')->rows(2)->columnSpanFull(),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('project_id')
            ->columns([
                Tables\Columns\TextColumn::make('project.name')->sortable()
                    ->badge()->color(fn ($record) => $record->project?->color ? null : 'gray'),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->color('gray')->size('sm'),
                Tables\Columns\TextColumn::make('tasks_count')->counts('tasks')->label('Tasks')->badge(),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')->label('Project')
                    ->options(fn () => Project::orderBy('sort_order')->pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit'   => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
