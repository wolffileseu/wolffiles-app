<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WikiArticleResource\Pages;
use App\Models\WikiArticle;
use App\Models\WikiCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class WikiArticleResource extends Resource
{
    protected static ?string $model = WikiArticle::class;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Wiki & Tutorials';
    protected static ?string $navigationLabel = 'Wiki Articles';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }


    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can('view_wiki_articles');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Wiki Article')->tabs([
                Forms\Components\Tabs\Tab::make('Content')->schema([
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('slug')
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->hint('Leave empty to auto-generate'),
                    Forms\Components\Select::make('wiki_category_id')
                        ->label('Category')
                        ->options(WikiCategory::where('is_active', true)->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    Forms\Components\Textarea::make('excerpt')
                        ->rows(2)
                        ->hint('Short summary. Auto-generated if empty.'),
                    Forms\Components\RichEditor::make('content')
                        ->required()
                        ->columnSpanFull()
                        ->fileAttachmentsDisk('s3')
                        ->fileAttachmentsDirectory('wiki/images')
                        ->fileAttachmentsVisibility('public')
                        ->toolbarButtons([
                            'attachFiles', 'blockquote', 'bold', 'bulletList', 'codeBlock',
                            'h2', 'h3', 'italic', 'link', 'orderedList', 'redo', 'strike',
                            'underline', 'undo', 'table',
                        ]),
                ]),
                Forms\Components\Tabs\Tab::make('Media & Attachments')->schema([
                    Forms\Components\FileUpload::make('attachments')
                        ->disk('s3')
                        ->directory('wiki/attachments')
                        ->multiple()
                        ->maxFiles(10)
                        ->maxSize(51200) // 50MB
                        ->acceptedFileTypes(['application/pdf', 'application/zip', 'application/x-rar-compressed', 'video/mp4', 'video/webm'])
                        ->label('Attachments (PDF, ZIP, Videos)')
                        ->columnSpanFull(),
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
                        ->hint('e.g. ET, Mapping, ETPub'),
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Toggle::make('is_featured')->label('Featured'),
                        Forms\Components\Toggle::make('is_locked')->label('Locked (no edits)'),
                    ]),
                    Forms\Components\DateTimePicker::make('published_at')
                        ->default(now()),
                    Forms\Components\KeyValue::make('title_translations')
                        ->label('Title Translations'),
                ]),
                Forms\Components\Tabs\Tab::make('Revisions')->schema([
                    Forms\Components\Placeholder::make('revision_info')
                        ->label('Revision History')
                        ->content(function (?WikiArticle $record) {
                            if (!$record) return 'Save the article first to see revisions.';
                            $revisions = $record->revisions()->with('user')->limit(10)->get();
                            if ($revisions->isEmpty()) return 'No revisions yet.';
                            $html = '<div class="space-y-2">';
                            foreach ($revisions as $rev) {
                                $html .= '<div class="text-sm">';
                                $html .= "<strong>v{$rev->revision_number}</strong> — ";
                                $html .= $rev->user->name ?? 'Unknown';
                                $html .= ' — ' . $rev->created_at->format('d.m.Y H:i');
                                if ($rev->change_summary) $html .= " — <em>{$rev->change_summary}</em>";
                                $html .= '</div>';
                            }
                            $html .= '</div>';
                            return new \Illuminate\Support\HtmlString($html);
                        })
                        ->columnSpanFull(),
                ])->visibleOn('edit'),
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
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'pending' => 'warning',
                        'draft' => 'gray',
                        'archived' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('view_count')->sortable(),
                Tables\Columns\TextColumn::make('revision_count')->label('Revisions')->sortable(),
                Tables\Columns\IconColumn::make('is_featured')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime('d.m.Y')->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['draft' => 'Draft', 'pending' => 'Pending', 'published' => 'Published', 'archived' => 'Archived']),
                Tables\Filters\SelectFilter::make('wiki_category_id')
                    ->label('Category')
                    ->options(WikiCategory::pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (WikiArticle $record) => $record->status === 'pending')
                    ->action(function (WikiArticle $record) {
                        $record->update(['status' => 'published', 'published_at' => now(), 'approved_by' => auth()->id()]);
                        Notification::make()->title('Article published!')->success()->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWikiArticles::route('/'),
            'create' => Pages\CreateWikiArticle::route('/create'),
            'edit' => Pages\EditWikiArticle::route('/{record}/edit'),
        ];
    }
}
