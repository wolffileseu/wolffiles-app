<?php

namespace App\Filament\Resources\BugTracker;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\BugTracker\ProjectResource\RelationManagers\CategoriesRelationManager;
use App\Filament\Resources\BugTracker\ProjectResource\RelationManagers\TasksRelationManager;
use App\Filament\Resources\BugTracker\ProjectResource\Pages\ListProjects;
use App\Filament\Resources\BugTracker\ProjectResource\Pages\CreateProject;
use App\Filament\Resources\BugTracker\ProjectResource\Pages\EditProject;
use App\Filament\Resources\BugTracker\ProjectResource\Pages;
use App\Filament\Resources\BugTracker\ProjectResource\RelationManagers;
use App\Models\BugTracker\Project;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-folder';
    protected static string | \UnitEnum | null $navigationGroup = 'Bug Tracker';
    protected static ?string $navigationLabel = 'Projects';
    protected static ?int $navigationSort = 10;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Basics')->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(120)->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, $set, $context) =>
                        $context === 'create' ? $set('slug', Str::slug($state)) : null),
                TextInput::make('slug')->required()->maxLength(60)->unique(ignoreRecord: true)
                    ->helperText('Used in task IDs (e.g. WOLFFILES-42)'),
                Textarea::make('description')->rows(3)->columnSpanFull(),
            ]),

            Section::make('Appearance')->columns(3)->schema([
                ColorPicker::make('color')->default('#6366f1'),
                TextInput::make('icon')->maxLength(16)->placeholder('🐺')
                    ->helperText('Single emoji or short symbol'),
                TextInput::make('sort_order')->numeric()->default(0),
            ]),

            Section::make('Defaults & Access')->columns(2)->schema([
                Select::make('default_assignee_id')
                    ->label('Default Assignee')
                    ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                    ->searchable()->nullable(),
                Toggle::make('is_public')->default(true)
                    ->helperText('Visible to non-logged-in users on bug.wolffiles.eu'),
                Toggle::make('is_active')->default(true),
            ]),

            Section::make('GitHub Sync')->columns(2)->schema([
                TextInput::make('github_repo')->maxLength(200)
                    ->placeholder('wolffileseu/wolffiles-app'),
                Toggle::make('github_sync_enabled')->default(false)
                    ->helperText('Push tasks & status changes to GitHub Issues'),
            ]),

            Section::make('Notifications')->columns(1)->collapsed()->schema([
                TextInput::make('discord_webhook_url')->maxLength(500)
                    ->url()->placeholder('https://discord.com/api/webhooks/...'),
                TextInput::make('telegram_chat_id')->maxLength(64),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('icon')->label(''),
                TextColumn::make('name')->searchable()->sortable()
                    ->description(fn (Project $r) => $r->slug),
                ColorColumn::make('color'),
                TextColumn::make('tasks_count')->counts('tasks')->label('Tasks')->sortable()
                    ->badge(),
                TextColumn::make('github_repo')->placeholder('—')->limit(30),
                IconColumn::make('github_sync_enabled')->boolean()->label('GH Sync'),
                IconColumn::make('is_public')->boolean()->label('Public'),
                IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->filters([
                TernaryFilter::make('is_active'),
                TernaryFilter::make('is_public'),
                TernaryFilter::make('github_sync_enabled')->label('GitHub Sync'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            CategoriesRelationManager::class,
            TasksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'edit'   => EditProject::route('/{record}/edit'),
        ];
    }
}
