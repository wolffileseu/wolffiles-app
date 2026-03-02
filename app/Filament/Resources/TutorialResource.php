<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TutorialResource\Pages;
use App\Models\Tutorial;
use App\Models\TutorialCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class TutorialResource extends Resource
{
    protected static ?string $model = Tutorial::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Wiki & Tutorials';
    protected static ?string $navigationLabel = 'Tutorials';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }


    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can('view_tutorials');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Tutorial')->tabs([
                Forms\Components\Tabs\Tab::make('Content')->schema([
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('slug')
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->hint('Leave empty to auto-generate'),
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\Select::make('tutorial_category_id')
                            ->label('Category')
                            ->options(TutorialCategory::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('difficulty')
                            ->options([
                                'beginner' => '🟢 Beginner',
                                'intermediate' => '🟡 Intermediate',
                                'advanced' => '🔴 Advanced',
                            ])
                            ->default('beginner')
                            ->required(),
                        Forms\Components\TextInput::make('estimated_minutes')
                            ->label('Duration (minutes)')
                            ->numeric()
                            ->hint('Estimated reading/follow-along time'),
                    ]),
                    Forms\Components\Textarea::make('excerpt')
                        ->rows(2)
                        ->hint('Short summary. Auto-generated if empty.'),
                    Forms\Components\Textarea::make('prerequisites')
                        ->rows(2)
                        ->hint('What does the reader need? e.g. "GTKRadiant installed, basic mapping knowledge"'),
                    Forms\Components\RichEditor::make('content')
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
                Forms\Components\Tabs\Tab::make('Video')->schema([
                    Forms\Components\TextInput::make('youtube_url')
                        ->label('YouTube URL')
                        ->url()
                        ->hint('Paste a YouTube link — auto-embedded on the page')
                        ->columnSpanFull(),
                    Forms\Components\FileUpload::make('video_path')
                        ->disk('s3')
                        ->directory('tutorials/videos')
                        ->label('Or upload a video')
                        ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/ogg'])
                        ->maxSize(512000) // 500MB
                        ->columnSpanFull(),
                ]),
                Forms\Components\Tabs\Tab::make('Attachments')->schema([
                    Forms\Components\FileUpload::make('attachments')
                        ->disk('s3')
                        ->directory('tutorials/attachments')
                        ->multiple()
                        ->maxFiles(10)
                        ->maxSize(102400) // 100MB
                        ->label('Attachments (project files, configs, PDFs)')
                        ->columnSpanFull(),
                ]),
                Forms\Components\Tabs\Tab::make('Steps')->schema([
                    Forms\Components\Repeater::make('steps')
                        ->relationship()
                        ->schema([
                            Forms\Components\TextInput::make('step_number')
                                ->numeric()
                                ->required()
                                ->default(fn ($get) => 1),
                            Forms\Components\TextInput::make('title')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),
                            Forms\Components\RichEditor::make('content')
                                ->required()
                                ->columnSpanFull()
                                ->fileAttachmentsDisk('s3')
                                ->fileAttachmentsDirectory('tutorials/step-images'),
                            Forms\Components\FileUpload::make('image_path')
                                ->disk('s3')
                                ->directory('tutorials/step-images')
                                ->image()
                                ->label('Step Screenshot'),
                            Forms\Components\TextInput::make('video_url')
                                ->url()
                                ->label('Step Video URL (YouTube)'),
                            Forms\Components\Textarea::make('tip')
                                ->rows(2)
                                ->label('💡 Pro Tip (optional)'),
                        ])
                        ->orderColumn('step_number')
                        ->collapsible()
                        ->cloneable()
                        ->itemLabel(fn (array $state): ?string => "Step {$state['step_number']}: " . ($state['title'] ?? ''))
                        ->columnSpanFull(),
                ]),
                Forms\Components\Tabs\Tab::make('Series')->schema([
                    Forms\Components\Toggle::make('is_series')
                        ->label('This is a multi-part tutorial series')
                        ->live(),
                    Forms\Components\Select::make('series_parent_id')
                        ->label('Part of series')
                        ->options(Tutorial::where('is_series', true)->pluck('title', 'id'))
                        ->searchable()
                        ->hint('Select the parent tutorial if this is part of a series')
                        ->visible(fn (Forms\Get $get) => !$get('is_series')),
                    Forms\Components\TextInput::make('series_order')
                        ->numeric()
                        ->default(0)
                        ->label('Order in series')
                        ->visible(fn (Forms\Get $get) => !$get('is_series')),
                ]),
                Forms\Components\Tabs\Tab::make('Settings')->schema([
                    Forms\Components\Select::make('status')
                        ->options([
                            'draft' => '📝 Draft',
                            'pending' => '⏳ Pending Review',
                            'published' => '✅ Published',
                            'archived' => '📦 Archived',
                        ])
                        ->default('draft')
                        ->required(),
                    Forms\Components\TagsInput::make('tags')
                        ->separator(',')
                        ->hint('e.g. Mapping, GTKRadiant, ET'),
                    Forms\Components\Toggle::make('is_featured')->label('Featured Tutorial'),
                    Forms\Components\DateTimePicker::make('published_at')->default(now()),
                    Forms\Components\KeyValue::make('title_translations')
                        ->label('Title Translations'),
                ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable()->limit(50),
                Tables\Columns\TextColumn::make('category.name')->badge()->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('Author')->sortable(),
                Tables\Columns\TextColumn::make('difficulty')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'beginner' => 'success',
                        'intermediate' => 'warning',
                        'advanced' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'pending' => 'warning',
                        'draft' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('view_count')->sortable(),
                Tables\Columns\TextColumn::make('helpful_count')->label('👍')->sortable(),
                Tables\Columns\IconColumn::make('is_featured')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime('d.m.Y')->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['draft' => 'Draft', 'pending' => 'Pending', 'published' => 'Published']),
                Tables\Filters\SelectFilter::make('difficulty')
                    ->options(['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced']),
                Tables\Filters\SelectFilter::make('tutorial_category_id')
                    ->label('Category')
                    ->options(TutorialCategory::pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Tutorial $record) => $record->status === 'pending')
                    ->action(function (Tutorial $record) {
                        $record->update(['status' => 'published', 'published_at' => now(), 'approved_by' => auth()->id()]);
                        Notification::make()->title('Tutorial published!')->success()->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTutorials::route('/'),
            'create' => Pages\CreateTutorial::route('/create'),
            'edit' => Pages\EditTutorial::route('/{record}/edit'),
        ];
    }
}
