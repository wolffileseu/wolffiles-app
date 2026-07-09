<?php

namespace App\Filament\Resources\BugTracker;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\BugTracker\TaskResource\RelationManagers\CommentsRelationManager;
use App\Filament\Resources\BugTracker\TaskResource\RelationManagers\AttachmentsRelationManager;
use App\Filament\Resources\BugTracker\TaskResource\RelationManagers\HistoryRelationManager;
use App\Filament\Resources\BugTracker\TaskResource\Pages\ListTasks;
use App\Filament\Resources\BugTracker\TaskResource\Pages\CreateTask;
use App\Filament\Resources\BugTracker\TaskResource\Pages\EditTask;
use App\Enums\BugTracker\TaskPriority;
use App\Enums\BugTracker\TaskSeverity;
use App\Enums\BugTracker\TaskStatus;
use App\Enums\BugTracker\TaskType;
use App\Filament\Resources\BugTracker\TaskResource\Pages;
use App\Filament\Resources\BugTracker\TaskResource\RelationManagers;
use App\Models\BugTracker\Category;
use App\Models\BugTracker\Project;
use App\Models\BugTracker\Tag;
use App\Models\BugTracker\Task;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-bug-ant';
    protected static string | \UnitEnum | null $navigationGroup = 'Bug Tracker';
    protected static ?string $navigationLabel = 'Tasks';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Task')->columns(2)->schema([
                Select::make('project_id')
                    ->label('Project')
                    ->options(fn () => Project::where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'))
                    ->required()->searchable()->live()
                    ->afterStateUpdated(fn ($set) => $set('category_id', null)),
                Select::make('category_id')
                    ->label('Category')
                    ->options(fn ($get) => $get('project_id')
                        ? Category::where('project_id', $get('project_id'))->orderBy('sort_order')->pluck('name', 'id')
                        : [])
                    ->searchable()->nullable(),
                TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
                Textarea::make('description')->required()->rows(10)->columnSpanFull()
                    ->helperText('Markdown supported'),
            ]),

            Section::make('Classification')->columns(4)->schema([
                Select::make('type')->options(collect(TaskType::cases())
                    ->mapWithKeys(fn ($c) => [$c->value => $c->icon().' '.$c->label()]))->default('bug')->required(),
                Select::make('status')->options(collect(TaskStatus::cases())
                    ->mapWithKeys(fn ($c) => [$c->value => $c->label()]))->default('new')->required(),
                Select::make('priority')->options(collect(TaskPriority::cases())
                    ->mapWithKeys(fn ($c) => [$c->value => $c->label()]))->default('normal')->required(),
                Select::make('severity')->options(collect(TaskSeverity::cases())
                    ->mapWithKeys(fn ($c) => [$c->value => $c->label()]))->default('minor')->required(),
            ]),

            Section::make('Assignment')->columns(2)->schema([
                Select::make('reporter_id')->label('Reporter')
                    ->options(fn () => User::orderBy('name')->pluck('name', 'id'))->searchable()->nullable(),
                Select::make('assignee_id')->label('Assignee')
                    ->options(fn () => User::orderBy('name')->pluck('name', 'id'))->searchable()->nullable(),
                TextInput::make('reporter_name')->maxLength(120)
                    ->helperText('Fallback name when Reporter user is unset (e.g. Discord)'),
                TextInput::make('reporter_email')->email()->maxLength(120),
            ]),

            Section::make('Versioning & Dates')->columns(3)->collapsed()->schema([
                TextInput::make('affected_version')->maxLength(50),
                TextInput::make('target_version')->maxLength(50),
                DatePicker::make('due_date'),
            ]),

            Section::make('Tags')->schema([
                Select::make('tags')->multiple()->relationship('tags', 'name')
                    ->preload()->searchable(),
            ]),

            Section::make('GitHub')->columns(2)->collapsed()->schema([
                TextInput::make('github_issue_number')->numeric()->disabled(),
                TextInput::make('github_issue_url')->url()->disabled(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_activity_at', 'desc')
            ->columns([
                TextColumn::make('display_id')->label('ID')
                    ->getStateUsing(fn (Task $r) => strtoupper($r->project?->slug ?? 'BT').'-'.$r->task_number)
                    ->color('gray')->size('sm')->copyable(),
                TextColumn::make('type')->badge()
                    ->formatStateUsing(fn (TaskType $state) => $state->icon().' '.$state->label())
                    ->color(fn () => 'gray'),
                TextColumn::make('title')->searchable()->limit(60)->weight('medium'),
                TextColumn::make('project.name')->badge()->sortable(),
                TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (TaskStatus $state) => $state->label())
                    ->color(fn (TaskStatus $state) => $state->color()),
                TextColumn::make('priority')->badge()
                    ->formatStateUsing(fn (TaskPriority $state) => $state->label())
                    ->color(fn (TaskPriority $state) => $state->color()),
                TextColumn::make('assignee.name')->placeholder('—')->toggleable(),
                TextColumn::make('last_activity_at')->since()->sortable()->toggleable(),
                TextColumn::make('created_at')->date()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('project_id')->label('Project')
                    ->options(fn () => Project::orderBy('sort_order')->pluck('name', 'id')),
                SelectFilter::make('status')->options(collect(TaskStatus::cases())
                    ->mapWithKeys(fn ($c) => [$c->value => $c->label()])),
                SelectFilter::make('priority')->options(collect(TaskPriority::cases())
                    ->mapWithKeys(fn ($c) => [$c->value => $c->label()])),
                SelectFilter::make('type')->options(collect(TaskType::cases())
                    ->mapWithKeys(fn ($c) => [$c->value => $c->label()])),
                SelectFilter::make('assignee_id')->label('Assignee')
                    ->options(fn () => User::orderBy('name')->pluck('name', 'id')),
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
            CommentsRelationManager::class,
            AttachmentsRelationManager::class,
            HistoryRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListTasks::route('/'),
            'create' => CreateTask::route('/create'),
            'edit'   => EditTask::route('/{record}/edit'),
        ];
    }
}
