<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WikiArticleResource\Pages;
use App\Models\WikiArticle;
use App\Models\WikiCategory;
use App\Services\Wiki\WikitextParser;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
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

                // ===== TAB 1: HAUPTDATEN =====
                Forms\Components\Tabs\Tab::make('Hauptdaten')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Titel (Master, Deutsch)')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state, ?WikiArticle $record) {
                                if (!$record && $state) {
                                    $set('slug', \Illuminate\Support\Str::slug($state));
                                }
                            })
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('slug')
                                ->maxLength(255)
                                ->required()
                                ->hint('URL-Pfad: /wiki/{slug}'),

                            Forms\Components\Select::make('namespace')
                                ->options([
                                    'main'     => 'Main (Standardartikel)',
                                    'help'     => 'Help: (Hilfe-Seiten)',
                                    'template' => 'Template: (Vorlagen)',
                                ])
                                ->default('main')
                                ->required(),

                            Forms\Components\Select::make('wiki_category_id')
                                ->label('Hauptkategorie (legacy)')
                                ->options(WikiCategory::where('is_active', true)->pluck('name', 'id'))
                                ->searchable()
                                ->hint('Optional. Auto-Sync aus [[Category:X]] im Wikitext.'),
                        ]),

                        Forms\Components\Textarea::make('excerpt')
                            ->rows(2)
                            ->hint('Kurzbeschreibung. Wird automatisch generiert falls leer.')
                            ->columnSpanFull(),
                    ]),

                // ===== TAB 2: WIKITEXT + PREVIEW =====
                Forms\Components\Tabs\Tab::make('Wikitext')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        Forms\Components\Placeholder::make('wikitext_help')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString(
                                '<div style="background:#1f2937; padding:0.75rem 1rem; border-radius:4px; font-size:13px; color:#d1d5db;">'
                                . '<strong style="color:#fbbf24;">Wikitext-Syntax:</strong> '
                                . '<code>== Heading ==</code> · '
                                . '<code>&#39;&#39;&#39;bold&#39;&#39;&#39;</code> · '
                                . '<code>&#39;&#39;italic&#39;&#39;</code> · '
                                . '<code>[[Link]]</code> · '
                                . '<code>[[Link|Text]]</code> · '
                                . '<code>* Liste</code> · '
                                . '<code>[[Category:Name]]</code> · '
                                . '<code>__TOC__</code>'
                                . '</div>'
                            ))
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Textarea::make('wikitext')
                                ->label('Wikitext-Quelle')
                                ->rows(24)
                                ->extraInputAttributes([
                                    'style' => 'font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 13px; line-height: 1.5;',
                                    'spellcheck' => 'false',
                                ])
                                ->live(debounce: 800)
                                ->afterStateUpdated(function (Set $set, ?string $state) {
                                    $set('_preview', static::renderPreview((string) $state));
                                }),

                            Forms\Components\Placeholder::make('_preview')
                                ->label('Live-Vorschau')
                                ->content(function (Get $get) {
                                    $wt = (string) $get('wikitext');
                                    if (trim($wt) === '') {
                                        return new \Illuminate\Support\HtmlString(
                                            '<div style="color:#9ca3af; font-style:italic;">Schreibe Wikitext links — Vorschau erscheint hier.</div>'
                                        );
                                    }
                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="wiki-skin"><div class="wiki-bodycontent" style="background:#1f2937; color:#e5e7eb; padding:1rem; border-radius:4px; max-height:600px; overflow-y:auto;">'
                                        . static::renderPreview($wt)
                                        . '</div></div>'
                                    );
                                }),
                        ]),

                        Forms\Components\TextInput::make('change_summary')
                            ->label('Änderungs-Zusammenfassung')
                            ->maxLength(255)
                            ->hint('Wird in Revisionshistorie angezeigt')
                            ->columnSpanFull()
                            ->dehydrated(false), // wird nicht in DB gespeichert, nur via request()->input() im Observer gelesen
                    ]),

                // ===== TAB 3: TRANSLATIONS =====
                Forms\Components\Tabs\Tab::make('Übersetzungen')
                    ->icon('heroicon-o-language')
                    ->schema([
                        Forms\Components\Placeholder::make('translations_help')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString(
                                '<div style="background:#1f2937; padding:0.5rem 0.75rem; border-radius:4px; font-size:12px; color:#9ca3af;">'
                                . 'Deutsch ist Master und wird oben im Wikitext-Tab editiert. Hier nur weitere Sprachen.'
                                . '</div>'
                            ))
                            ->columnSpanFull(),

                        Forms\Components\Repeater::make('translations')
                            ->relationship(modifyQueryUsing: fn ($query) => $query->where('locale', '!=', 'de'))
                            ->schema([
                                Forms\Components\Select::make('locale')
                                    ->options([
                                        'en' => 'English',
                                        'fr' => 'Français',
                                        'nl' => 'Nederlands',
                                        'pl' => 'Polski',
                                        'tr' => 'Türkçe',
                                    ])
                                    ->required()
                                    ->distinct(),
                                Forms\Components\TextInput::make('title')->required()->maxLength(255),
                                Forms\Components\Textarea::make('wikitext')
                                    ->rows(12)
                                    ->extraInputAttributes(['style' => 'font-family: monospace; font-size: 13px;']),
                            ])
                            ->columns(1)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string =>
                                ($state['locale'] ?? '?') . ' — ' . ($state['title'] ?? 'Neu')
                            )
                            ->defaultItems(0)
                            ->addActionLabel('+ Sprache hinzufügen')
                            ->columnSpanFull(),
                    ]),

                // ===== TAB 4: MEDIA =====
                Forms\Components\Tabs\Tab::make('Anhänge')
                    ->icon('heroicon-o-paper-clip')
                    ->schema([
                        Forms\Components\FileUpload::make('attachments')
                            ->disk('s3')
                            ->directory('wiki/attachments')
                            ->multiple()
                            ->maxFiles(10)
                            ->maxSize(51200)
                            ->acceptedFileTypes(['application/pdf', 'application/zip', 'application/x-rar-compressed', 'video/mp4', 'video/webm'])
                            ->label('Anhänge (PDF, ZIP, Videos)')
                            ->columnSpanFull(),
                    ]),

                // ===== TAB 5: SETTINGS =====
                Forms\Components\Tabs\Tab::make('Einstellungen')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft'     => '📝 Entwurf',
                                'pending'   => '⏳ Wartet auf Review',
                                'published' => '✅ Veröffentlicht',
                                'archived'  => '📦 Archiviert',
                            ])
                            ->default('draft')
                            ->required(),
                        Forms\Components\TagsInput::make('tags')
                            ->separator(',')
                            ->hint('z.B. ET, Mapping, ETPub'),
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Toggle::make('is_featured')->label('Featured'),
                            Forms\Components\Toggle::make('is_locked')->label('Locked (keine Edits)'),
                        ]),
                        Forms\Components\DateTimePicker::make('published_at')->default(now()),
                    ]),

                // ===== TAB 6: REVISIONS =====
                Forms\Components\Tabs\Tab::make('Versionshistorie')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Forms\Components\Placeholder::make('revision_info')
                            ->label('')
                            ->content(function (?WikiArticle $record) {
                                if (!$record) return 'Speichere den Artikel um Revisionen zu sehen.';
                                $revisions = $record->revisions()->with('user')->limit(20)->get();
                                if ($revisions->isEmpty()) return 'Noch keine Revisionen.';
                                $html = '<div class="space-y-1">';
                                foreach ($revisions as $rev) {
                                    $html .= '<div style="padding:0.4rem; border-bottom:1px solid #374151; font-size:13px;">';
                                    $html .= '<strong>v' . $rev->revision_number . '</strong> — ';
                                    $html .= htmlspecialchars($rev->user->name ?? 'Unbekannt');
                                    $html .= ' — ' . $rev->created_at->format('d.m.Y H:i');
                                    if ($rev->change_summary) {
                                        $html .= ' — <em style="color:#9ca3af;">' . htmlspecialchars($rev->change_summary) . '</em>';
                                    }
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

    /**
     * Render-Helper für Live-Preview.
     */
    protected static function renderPreview(string $wikitext): string
    {
        if (trim($wikitext) === '') return '';
        try {
            $parser = WikitextParser::make(['locale' => 'de', 'namespace' => 'main']);
            return $parser->parse($wikitext)->html;
        } catch (\Throwable $e) {
            return '<div style="color:#ef4444;">Render-Fehler: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable()->limit(50),
                Tables\Columns\TextColumn::make('namespace')->badge()->sortable(),
                Tables\Columns\TextColumn::make('category.name')->badge()->sortable()->label('Cat'),
                Tables\Columns\TextColumn::make('user.name')->label('Autor')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'pending'   => 'warning',
                        'draft'     => 'gray',
                        'archived'  => 'danger',
                        default     => 'gray',
                    }),
                Tables\Columns\TextColumn::make('view_count')->sortable()->label('Views'),
                Tables\Columns\TextColumn::make('revision_count')->label('Rev')->sortable(),
                Tables\Columns\IconColumn::make('is_featured')->boolean(),
                Tables\Columns\IconColumn::make('is_redirect')->boolean()->label('↪'),
                Tables\Columns\TextColumn::make('updated_at')->dateTime('d.m.Y')->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['draft' => 'Draft', 'pending' => 'Pending', 'published' => 'Published', 'archived' => 'Archived']),
                Tables\Filters\SelectFilter::make('namespace')
                    ->options(['main' => 'Main', 'help' => 'Help', 'template' => 'Template']),
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
                Tables\Actions\Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->url(fn (WikiArticle $record) => route('wiki.show', $record->slug))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListWikiArticles::route('/'),
            'create' => Pages\CreateWikiArticle::route('/create'),
            'edit'   => Pages\EditWikiArticle::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteKeyName(): string
    {
        return 'slug';
    }
}
