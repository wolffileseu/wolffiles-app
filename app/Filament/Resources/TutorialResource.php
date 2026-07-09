<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\TutorialResource\Pages\ListTutorials;
use App\Filament\Resources\TutorialResource\Pages\CreateTutorial;
use App\Filament\Resources\TutorialResource\Pages\EditTutorial;
use App\Filament\Resources\TutorialResource\Pages;
use App\Models\Tutorial;
use App\Models\TutorialCategory;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class TutorialResource extends Resource
{
    protected static ?string $model = Tutorial::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';
    protected static string | \UnitEnum | null $navigationGroup = 'Wiki & Tutorials';
    protected static ?string $navigationLabel = 'Tutorials';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }



    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Tutorial')->tabs([
                Tab::make('Content')->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    TextInput::make('slug')
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->hint('Leave empty to auto-generate'),
                    Grid::make(3)->schema([
                        Select::make('tutorial_category_id')
                            ->label('Category')
                            ->options(TutorialCategory::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Select::make('difficulty')
                            ->options([
                                'beginner' => '🟢 Beginner',
                                'intermediate' => '🟡 Intermediate',
                                'advanced' => '🔴 Advanced',
                            ])
                            ->default('beginner')
                            ->required(),
                        TextInput::make('estimated_minutes')
                            ->label('Duration (minutes)')
                            ->numeric()
                            ->hint('Estimated reading/follow-along time'),
                    ]),
                    Textarea::make('excerpt')
                        ->rows(2)
                        ->hint('Short summary. Auto-generated if empty.'),
                    Textarea::make('prerequisites')
                        ->rows(2)
                        ->hint('What does the reader need? e.g. "GTKRadiant installed, basic mapping knowledge"'),
                    RichEditor::make('content')
                        ->required()
                        ->columnSpanFull()
                        ->fileAttachmentsDisk('s3')
                        ->fileAttachmentsDirectory('tutorials/images')
                        ->fileAttachmentsVisibility('public')
                        ->toolbarButtons([
                            'attachFiles', 'blockquote', 'bold', 'bulletList', 'codeBlock',
                            'h2', 'h3', 'italic', 'link', 'orderedList', 'redo', 'strike',
                            'underline', 'undo', 'table',
                        ]),
                ]),
                Tab::make('Video')->schema([
                    TextInput::make('youtube_url')
                        ->label('YouTube URL')
                        ->url()
                        ->hint('Paste a YouTube link — auto-embedded on the page')
                        ->columnSpanFull(),
                    FileUpload::make('video_path')
                        ->disk('s3')
                        ->directory('tutorials/videos')->visibility('public')
                        ->label('Or upload a video')
                        ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/ogg'])
                        ->maxSize(512000) // 500MB
                        ->columnSpanFull(),
                ]),
                Tab::make('Attachments')->schema([
                    FileUpload::make('attachments')
                        ->disk('s3')
                        ->directory('tutorials/attachments')->visibility('public')
                        ->multiple()
                        ->maxFiles(10)
                        ->maxSize(102400) // 100MB
                        ->label('Attachments (project files, configs, PDFs)')
                        ->columnSpanFull(),
                ]),
                Tab::make('Steps')->schema([
                    Repeater::make('steps')
                        ->relationship()
                        ->schema([
                            TextInput::make('step_number')
                                ->numeric()
                                ->required()
                                ->default(fn ($get) => 1),
                            TextInput::make('title')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),
                            RichEditor::make('content')
                                ->required()
                                ->columnSpanFull()
                                ->fileAttachmentsDisk('s3')
                                ->fileAttachmentsDirectory('tutorials/step-images'),
                            FileUpload::make('image_path')
                                ->disk('s3')
                                ->directory('tutorials/step-images')->visibility('public')
                                ->image()
                                ->label('Step Screenshot'),
                            TextInput::make('video_url')
                                ->url()
                                ->label('Step Video URL (YouTube)'),
                            Textarea::make('tip')
                                ->rows(2)
                                ->label('💡 Pro Tip (optional)'),
                        ])
                        ->orderColumn('step_number')
                        ->collapsible()
                        ->cloneable()
                        ->itemLabel(fn (array $state): ?string => "Step {$state['step_number']}: " . ($state['title'] ?? ''))
                        ->columnSpanFull(),
                ]),
                Tab::make('Series')->schema([
                    Toggle::make('is_series')
                        ->label('This is a multi-part tutorial series')
                        ->live(),
                    Select::make('series_parent_id')
                        ->label('Part of series')
                        ->options(Tutorial::where('is_series', true)->pluck('title', 'id'))
                        ->searchable()
                        ->hint('Select the parent tutorial if this is part of a series')
                        ->visible(fn (Get $get) => !$get('is_series')),
                    TextInput::make('series_order')
                        ->numeric()
                        ->default(0)
                        ->label('Order in series')
                        ->visible(fn (Get $get) => !$get('is_series')),
                ]),
                Tab::make('Settings')->schema([
                    Select::make('status')
                        ->options([
                            'draft' => '📝 Draft',
                            'pending' => '⏳ Pending Review',
                            'published' => '✅ Published',
                            'archived' => '📦 Archived',
                        ])
                        ->default('draft')
                        ->required(),
                    TagsInput::make('tags')
                        ->separator(',')
                        ->hint('e.g. Mapping, GTKRadiant, ET'),
                    Toggle::make('is_featured')->label('Featured Tutorial'),
                    DateTimePicker::make('published_at')->default(now()),
                    KeyValue::make('title_translations')
                        ->label('Title Translations'),
                ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable()->limit(50),
                TextColumn::make('category.name')->badge()->sortable(),
                TextColumn::make('user.name')->label('Author')->sortable(),
                TextColumn::make('difficulty')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'beginner' => 'success',
                        'intermediate' => 'warning',
                        'advanced' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'pending' => 'warning',
                        'draft' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('view_count')->sortable(),
                TextColumn::make('helpful_count')->label('👍')->sortable(),
                IconColumn::make('is_featured')->boolean(),
                TextColumn::make('updated_at')->dateTime('d.m.Y')->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(['draft' => 'Draft', 'pending' => 'Pending', 'published' => 'Published']),
                SelectFilter::make('difficulty')
                    ->options(['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced']),
                SelectFilter::make('tutorial_category_id')
                    ->label('Category')
                    ->options(TutorialCategory::pluck('name', 'id')),
            ])
            ->recordActions([
                Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Tutorial $record) => $record->status === 'pending')
                    ->action(function (Tutorial $record) {
                        $record->update(['status' => 'published', 'published_at' => now(), 'approved_by' => auth()->id()]);
                        Notification::make()->title('Tutorial published!')->success()->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTutorials::route('/'),
            'create' => CreateTutorial::route('/create'),
            'edit' => EditTutorial::route('/{record}/edit'),
        ];
    }
}
