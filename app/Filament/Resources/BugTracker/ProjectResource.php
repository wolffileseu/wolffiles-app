<?php

namespace App\Filament\Resources\BugTracker;

use App\Filament\Resources\BugTracker\ProjectResource\Pages;
use App\Filament\Resources\BugTracker\ProjectResource\RelationManagers;
use App\Models\BugTracker\Project;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationGroup = 'Bug Tracker';
    protected static ?string $navigationLabel = 'Projects';
    protected static ?int $navigationSort = 10;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Basics')->columns(2)->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(120)->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, $set, $context) =>
                        $context === 'create' ? $set('slug', \Illuminate\Support\Str::slug($state)) : null),
                Forms\Components\TextInput::make('slug')->required()->maxLength(60)->unique(ignoreRecord: true)
                    ->helperText('Used in task IDs (e.g. WOLFFILES-42)'),
                Forms\Components\Textarea::make('description')->rows(3)->columnSpanFull(),
            ]),

            Forms\Components\Section::make('Appearance')->columns(3)->schema([
                Forms\Components\ColorPicker::make('color')->default('#6366f1'),
                Forms\Components\TextInput::make('icon')->maxLength(16)->placeholder('🐺')
                    ->helperText('Single emoji or short symbol'),
                Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
            ]),

            Forms\Components\Section::make('Defaults & Access')->columns(2)->schema([
                Forms\Components\Select::make('default_assignee_id')
                    ->label('Default Assignee')
                    ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                    ->searchable()->nullable(),
                Forms\Components\Toggle::make('is_public')->default(true)
                    ->helperText('Visible to non-logged-in users on bug.wolffiles.eu'),
                Forms\Components\Toggle::make('is_active')->default(true),
            ]),

            Forms\Components\Section::make('GitHub Sync')->columns(2)->schema([
                Forms\Components\TextInput::make('github_repo')->maxLength(200)
                    ->placeholder('wolffileseu/wolffiles-app'),
                Forms\Components\Toggle::make('github_sync_enabled')->default(false)
                    ->helperText('Push tasks & status changes to GitHub Issues'),
            ]),

            Forms\Components\Section::make('Notifications')->columns(1)->collapsed()->schema([
                Forms\Components\TextInput::make('discord_webhook_url')->maxLength(500)
                    ->url()->placeholder('https://discord.com/api/webhooks/...'),
                Forms\Components\TextInput::make('telegram_chat_id')->maxLength(64),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('icon')->label(''),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable()
                    ->description(fn (Project $r) => $r->slug),
                Tables\Columns\ColorColumn::make('color'),
                Tables\Columns\TextColumn::make('tasks_count')->counts('tasks')->label('Tasks')->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('github_repo')->placeholder('—')->limit(30),
                Tables\Columns\IconColumn::make('github_sync_enabled')->boolean()->label('GH Sync'),
                Tables\Columns\IconColumn::make('is_public')->boolean()->label('Public'),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\TernaryFilter::make('is_public'),
                Tables\Filters\TernaryFilter::make('github_sync_enabled')->label('GitHub Sync'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CategoriesRelationManager::class,
            RelationManagers\TasksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit'   => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
