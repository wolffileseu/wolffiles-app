<?php

namespace App\Filament\Resources\BugTracker;

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
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;
    protected static ?string $navigationIcon = 'heroicon-o-bug-ant';
    protected static ?string $navigationGroup = 'Bug Tracker';
    protected static ?string $navigationLabel = 'Tasks';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Task')->columns(2)->schema([
                Forms\Components\Select::make('project_id')
                    ->label('Project')
                    ->options(fn () => Project::where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'))
                    ->required()->searchable()->live()
                    ->afterStateUpdated(fn ($set) => $set('category_id', null)),
                Forms\Components\Select::make('category_id')
                    ->label('Category')
                    ->options(fn ($get) => $get('project_id')
                        ? Category::where('project_id', $get('project_id'))->orderBy('sort_order')->pluck('name', 'id')
                        : [])
                    ->searchable()->nullable(),
                Forms\Components\TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
                Forms\Components\Textarea::make('description')->required()->rows(10)->columnSpanFull()
                    ->helperText('Markdown supported'),
            ]),

            Forms\Components\Section::make('Classification')->columns(4)->schema([
                Forms\Components\Select::make('type')->options(collect(TaskType::cases())
                    ->mapWithKeys(fn ($c) => [$c->value => $c->icon().' '.$c->label()]))->default('bug')->required(),
                Forms\Components\Select::make('status')->options(collect(TaskStatus::cases())
                    ->mapWithKeys(fn ($c) => [$c->value => $c->label()]))->default('new')->required(),
                Forms\Components\Select::make('priority')->options(collect(TaskPriority::cases())
                    ->mapWithKeys(fn ($c) => [$c->value => $c->label()]))->default('normal')->required(),
                Forms\Components\Select::make('severity')->options(collect(TaskSeverity::cases())
                    ->mapWithKeys(fn ($c) => [$c->value => $c->label()]))->default('minor')->required(),
            ]),

            Forms\Components\Section::make('Assignment')->columns(2)->schema([
                Forms\Components\Select::make('reporter_id')->label('Reporter')
                    ->options(fn () => User::orderBy('name')->pluck('name', 'id'))->searchable()->nullable(),
                Forms\Components\Select::make('assignee_id')->label('Assignee')
                    ->options(fn () => User::orderBy('name')->pluck('name', 'id'))->searchable()->nullable(),
                Forms\Components\TextInput::make('reporter_name')->maxLength(120)
                    ->helperText('Fallback name when Reporter user is unset (e.g. Discord)'),
                Forms\Components\TextInput::make('reporter_email')->email()->maxLength(120),
            ]),

            Forms\Components\Section::make('Versioning & Dates')->columns(3)->collapsed()->schema([
                Forms\Components\TextInput::make('affected_version')->maxLength(50),
                Forms\Components\TextInput::make('target_version')->maxLength(50),
                Forms\Components\DatePicker::make('due_date'),
            ]),

            Forms\Components\Section::make('Tags')->schema([
                Forms\Components\Select::make('tags')->multiple()->relationship('tags', 'name')
                    ->preload()->searchable(),
            ]),

            Forms\Components\Section::make('GitHub')->columns(2)->collapsed()->schema([
                Forms\Components\TextInput::make('github_issue_number')->numeric()->disabled(),
                Forms\Components\TextInput::make('github_issue_url')->url()->disabled(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_activity_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('display_id')->label('ID')
                    ->getStateUsing(fn (Task $r) => strtoupper($r->project?->slug ?? 'BT').'-'.$r->task_number)
                    ->color('gray')->size('sm')->copyable(),
                Tables\Columns\TextColumn::make('type')->badge()
                    ->formatStateUsing(fn (TaskType $state) => $state->icon().' '.$state->label())
                    ->color(fn () => 'gray'),
                Tables\Columns\TextColumn::make('title')->searchable()->limit(60)->weight('medium'),
                Tables\Columns\TextColumn::make('project.name')->badge()->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (TaskStatus $state) => $state->label())
                    ->color(fn (TaskStatus $state) => $state->color()),
                Tables\Columns\TextColumn::make('priority')->badge()
                    ->formatStateUsing(fn (TaskPriority $state) => $state->label())
                    ->color(fn (TaskPriority $state) => $state->color()),
                Tables\Columns\TextColumn::make('assignee.name')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('last_activity_at')->since()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->date()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')->label('Project')
                    ->options(fn () => Project::orderBy('sort_order')->pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('status')->options(collect(TaskStatus::cases())
                    ->mapWithKeys(fn ($c) => [$c->value => $c->label()])),
                Tables\Filters\SelectFilter::make('priority')->options(collect(TaskPriority::cases())
                    ->mapWithKeys(fn ($c) => [$c->value => $c->label()])),
                Tables\Filters\SelectFilter::make('type')->options(collect(TaskType::cases())
                    ->mapWithKeys(fn ($c) => [$c->value => $c->label()])),
                Tables\Filters\SelectFilter::make('assignee_id')->label('Assignee')
                    ->options(fn () => User::orderBy('name')->pluck('name', 'id')),
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
            RelationManagers\CommentsRelationManager::class,
            RelationManagers\AttachmentsRelationManager::class,
            RelationManagers\HistoryRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit'   => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}
