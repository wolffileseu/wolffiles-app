<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Str;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\HtmlString;
use Filament\Schemas\Components\Actions;
use Filament\Actions\Action;
use App\Models\WikiMedia;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use App\Services\Wiki\WikiMediaService;
use Throwable;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\WikiArticleResource\Pages\ListWikiArticles;
use App\Filament\Resources\WikiArticleResource\Pages\CreateWikiArticle;
use App\Filament\Resources\WikiArticleResource\Pages\EditWikiArticle;
use App\Filament\Resources\WikiArticleResource\Pages;
use App\Models\WikiArticle;
use App\Models\WikiCategory;
use App\Services\Wiki\WikitextParser;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class WikiArticleResource extends Resource
{
    protected static ?string $model = WikiArticle::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-book-open';
    protected static string | \UnitEnum | null $navigationGroup = 'Wiki & Tutorials';
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


    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Wiki Article')->tabs([

                // ===== TAB 1: HAUPTDATEN =====
                Tab::make('Hauptdaten')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        TextInput::make('title')
                            ->label('Titel (Master, Deutsch)')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state, ?WikiArticle $record) {
                                if (!$record && $state) {
                                    $set('slug', Str::slug($state));
                                }
                            })
                            ->columnSpanFull(),

                        Grid::make(3)->schema([
                            TextInput::make('slug')
                                ->maxLength(255)
                                ->required()
                                ->hint('URL-Pfad: /wiki/{slug}'),

                            Select::make('namespace')
                                ->options([
                                    'main'     => 'Main (Standardartikel)',
                                    'help'     => 'Help: (Hilfe-Seiten)',
                                    'template' => 'Template: (Vorlagen)',
                                ])
                                ->default('main')
                                ->required(),

                            Select::make('wiki_category_id')
                                ->label('Hauptkategorie (legacy)')
                                ->options(WikiCategory::where('is_active', true)->pluck('name', 'id'))
                                ->searchable()
                                ->hint('Optional. Auto-Sync aus [[Category:X]] im Wikitext.'),
                        ]),

                        Textarea::make('excerpt')
                            ->rows(2)
                            ->hint('Kurzbeschreibung. Wird automatisch generiert falls leer.')
                            ->columnSpanFull(),
                    ]),

                // ===== TAB 2: WIKITEXT + PREVIEW =====
                Tab::make('Wikitext')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        Placeholder::make('wikitext_help')
                            ->label('')
                            ->content(new HtmlString(
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

                        // ----- Bild-Einfuegen-Action -----
                        Actions::make([
                            Action::make('insertImage')
                                ->label('Bild einfügen')
                                ->icon('heroicon-o-photo')
                                ->color('primary')
                                ->modalHeading('🖼 Bild ins Wikitext einfügen')
                                ->modalDescription('Wähle ein Bild aus dem Pool oder lade ein neues hoch.')
                                ->modalWidth('2xl')
                                ->modalSubmitActionLabel('Einfügen')
                                ->schema([
                                    Tabs::make('imageSource')->tabs([

                                        Tab::make('Aus Pool')
                                            ->icon('heroicon-o-photo')
                                            ->schema([
                                                Select::make('pool_media_id')
                                                    ->label('Vorhandenes Bild')
                                                    ->placeholder('Bild aus Pool wählen oder suchen…')
                                                    ->searchable()
                                                    ->options(fn () => WikiMedia::query()
                                                        ->where('type', 'image')
                                                        ->orderByDesc('id')
                                                        ->limit(50)
                                                        ->pluck('filename', 'id')
                                                        ->toArray())
                                                    ->getSearchResultsUsing(function (string $search) {
                                                        $like = '%' . $search . '%';
                                                        return WikiMedia::query()
                                                            ->where('type', 'image')
                                                            ->where(function ($q) use ($like) {
                                                                $q->where('filename', 'like', $like)
                                                                  ->orWhere('caption', 'like', $like);
                                                            })
                                                            ->limit(30)
                                                            ->pluck('filename', 'id')
                                                            ->toArray();
                                                    })
                                                    ->getOptionLabelUsing(fn ($value) => WikiMedia::find($value)?->filename)
                                                    ->live(),

                                                Placeholder::make('pool_preview')
                                                    ->label('')
                                                    ->content(function (Get $get) {
                                                        $id = $get('pool_media_id');
                                                        if (! $id) {
                                                            return new HtmlString(
                                                                '<div style="color:#9ca3af; font-style:italic; padding:1rem; text-align:center;">Wähle ein Bild oben, um eine Vorschau zu sehen.</div>'
                                                            );
                                                        }
                                                        $m = WikiMedia::find($id);
                                                        if (! $m) return '';
                                                        return new HtmlString(
                                                            '<div style="text-align:center; padding:0.5rem 0;">'
                                                            . '<img src="' . htmlspecialchars($m->url, ENT_QUOTES) . '" alt="" '
                                                            . 'style="max-width:100%; max-height:240px; border-radius:6px; border:1px solid rgba(255,255,255,0.1);" />'
                                                            . '<div style="margin-top:0.5rem; color:#9ca3af; font-size:12px;">'
                                                            . htmlspecialchars($m->filename) . ' · ' . htmlspecialchars($m->file_size_formatted)
                                                            . '</div></div>'
                                                        );
                                                    }),
                                            ]),

                                        Tab::make('Neu hochladen')
                                            ->icon('heroicon-o-arrow-up-tray')
                                            ->schema([
                                                FileUpload::make('upload_file')
                                                    ->label('Bild-Datei')
                                                    ->image()
                                                    ->maxSize(8 * 1024) // 8 MB in KB
                                                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/gif', 'image/webp'])
                                                    ->directory('tmp-wiki-uploads')
                                                    ->disk('public')
                                                    ->helperText('PNG / JPG / GIF / WEBP, max 8 MB. Wird beim Einfügen automatisch in den Wiki-Pool übernommen.'),
                                            ]),

                                    ]),

                                    Section::make('Anzeige-Optionen')
                                        ->collapsible()
                                        ->schema([
                                            TextInput::make('caption')
                                                ->label('Bildunterschrift')
                                                ->placeholder('z.B. „ETF Hauptmenü mit allen Slots"')
                                                ->columnSpanFull(),
                                            Select::make('size')
                                                ->label('Größe')
                                                ->options([
                                                    'thumb-200'  => 'Thumb · 200px',
                                                    'thumb-400'  => 'Thumb · 400px (Standard)',
                                                    'thumb-600'  => 'Thumb · 600px',
                                                    'thumb-only' => 'Thumb · Standardgröße',
                                                    'full'       => 'Volle Breite',
                                                ])
                                                ->default('thumb-400')
                                                ->required(),
                                            Select::make('align')
                                                ->label('Ausrichtung')
                                                ->options([
                                                    'none'   => 'Standard',
                                                    'left'   => 'Links',
                                                    'right'  => 'Rechts',
                                                    'center' => 'Zentriert',
                                                ])
                                                ->default('none'),
                                        ])->columns(2),
                                ])
                                ->action(function (array $data, $livewire): void {
                                    // 1) Bild bestimmen — Pool oder neu hochgeladen
                                    $media = null;

                                    if (! empty($data['upload_file'])) {
                                        $tmpRel = is_array($data['upload_file']) ? reset($data['upload_file']) : $data['upload_file'];
                                        if ($tmpRel) {
                                            $abs = Storage::disk('public')->path($tmpRel);
                                            if (file_exists($abs)) {
                                                $file = new UploadedFile(
                                                    $abs,
                                                    basename($tmpRel),
                                                    mime_content_type($abs) ?: 'image/png',
                                                    null,
                                                    true
                                                );
                                                try {
                                                    $media = (new WikiMediaService())
                                                        ->store($file, auth()->id());
                                                } catch (Throwable $e) {
                                                    Notification::make()
                                                        ->title('Upload fehlgeschlagen')
                                                        ->body($e->getMessage())
                                                        ->danger()->send();
                                                    return;
                                                }
                                                @unlink($abs);
                                            }
                                        }
                                    } elseif (! empty($data['pool_media_id'])) {
                                        $media = WikiMedia::find($data['pool_media_id']);
                                    }

                                    if (! $media) {
                                        Notification::make()
                                            ->title('Kein Bild gewählt')
                                            ->body('Wähle ein Bild aus dem Pool oder lade eines neu hoch.')
                                            ->warning()->send();
                                        return;
                                    }

                                    // 2) Snippet bauen
                                    $opts = [];
                                    switch ($data['size'] ?? 'thumb-400') {
                                        case 'thumb-200':  $opts[] = 'thumb'; $opts[] = '200px'; break;
                                        case 'thumb-400':  $opts[] = 'thumb'; $opts[] = '400px'; break;
                                        case 'thumb-600':  $opts[] = 'thumb'; $opts[] = '600px'; break;
                                        case 'thumb-only': $opts[] = 'thumb'; break;
                                        case 'full':       /* nichts */ break;
                                    }
                                    if (in_array($data['align'] ?? 'none', ['left', 'right', 'center'], true)) {
                                        $opts[] = $data['align'];
                                    }
                                    if (! empty($data['caption'])) {
                                        $opts[] = $data['caption'];
                                    }

                                    $snippet = '[[File:' . $media->filename
                                        . (count($opts) > 0 ? '|' . implode('|', $opts) : '')
                                        . ']]';

                                    // 3) An den CM6-Editor schicken (Livewire dispatch -> Window-Event)
                                    $livewire->dispatch('wikitext-insert', snippet: $snippet);

                                    Notification::make()
                                        ->title('Bild eingefügt')
                                        ->body($snippet)
                                        ->success()->send();
                                }),
                        ])->columnSpanFull(),

                        Grid::make(2)->schema([
                            ViewField::make('wikitext')
                                ->label('Wikitext-Quelle')
                                ->view('forms.components.wikitext-editor')
                                ->live(debounce: 800)
                                ->afterStateUpdated(function (Set $set, ?string $state) {
                                    $set('_preview', static::renderPreview((string) $state));
                                }),

                            Placeholder::make('_preview')
                                ->label('Live-Vorschau')
                                ->content(function (Get $get) {
                                    $wt = (string) $get('wikitext');
                                    if (trim($wt) === '') {
                                        return new HtmlString(
                                            '<div style="color:#9ca3af; font-style:italic;">Schreibe Wikitext links — Vorschau erscheint hier.</div>'
                                        );
                                    }
                                    return new HtmlString(
                                        '<div class="wiki-skin"><div class="wiki-bodycontent" style="background:#1f2937; color:#e5e7eb; padding:1rem; border-radius:4px; max-height:600px; overflow-y:auto;">'
                                        . static::renderPreview($wt)
                                        . '</div></div>'
                                    );
                                }),
                        ]),

                        TextInput::make('change_summary')
                            ->label('Änderungs-Zusammenfassung')
                            ->maxLength(255)
                            ->hint('Wird in Revisionshistorie angezeigt')
                            ->columnSpanFull()
                            ->dehydrated(false), // wird nicht in DB gespeichert, nur via request()->input() im Observer gelesen
                    ]),

                // ===== TAB 3: TRANSLATIONS =====
                Tab::make('Übersetzungen')
                    ->icon('heroicon-o-language')
                    ->schema([
                        Placeholder::make('translations_help')
                            ->label('')
                            ->content(new HtmlString(
                                '<div style="background:#1f2937; padding:0.5rem 0.75rem; border-radius:4px; font-size:12px; color:#9ca3af;">'
                                . 'Deutsch ist Master und wird oben im Wikitext-Tab editiert. Hier nur weitere Sprachen.'
                                . '</div>'
                            ))
                            ->columnSpanFull(),

                        Repeater::make('translations')
                            ->relationship(modifyQueryUsing: fn ($query) => $query->where('locale', '!=', 'de'))
                            ->schema([
                                Select::make('locale')
                                    ->options([
                                        'en' => 'English',
                                        'fr' => 'Français',
                                        'nl' => 'Nederlands',
                                        'pl' => 'Polski',
                                        'tr' => 'Türkçe',
                                    ])
                                    ->required()
                                    ->distinct(),
                                TextInput::make('title')->required()->maxLength(255),
                                Textarea::make('wikitext')
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
                Tab::make('Anhänge')
                    ->icon('heroicon-o-paper-clip')
                    ->schema([
                        FileUpload::make('attachments')
                            ->disk('s3')
                            ->directory('wiki/attachments')->visibility('public')
                            ->multiple()
                            ->maxFiles(10)
                            ->maxSize(51200)
                            ->acceptedFileTypes(['application/pdf', 'application/zip', 'application/x-rar-compressed', 'video/mp4', 'video/webm'])
                            ->label('Anhänge (PDF, ZIP, Videos)')
                            ->columnSpanFull(),
                    ]),

                // ===== TAB 5: SETTINGS =====
                Tab::make('Einstellungen')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'draft'     => '📝 Entwurf',
                                'pending'   => '⏳ Wartet auf Review',
                                'published' => '✅ Veröffentlicht',
                                'archived'  => '📦 Archiviert',
                            ])
                            ->default('draft')
                            ->required(),
                        TagsInput::make('tags')
                            ->separator(',')
                            ->hint('z.B. ET, Mapping, ETPub'),
                        Grid::make(2)->schema([
                            Toggle::make('is_featured')->label('Featured'),
                            Toggle::make('is_locked')->label('Locked (keine Edits)'),
                        ]),
                        DateTimePicker::make('published_at')->default(now()),
                    ]),

                // ===== TAB 6: REVISIONS =====
                Tab::make('Versionshistorie')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Placeholder::make('revision_info')
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
                                return new HtmlString($html);
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
        } catch (Throwable $e) {
            return '<div style="color:#ef4444;">Render-Fehler: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable()->limit(50),
                TextColumn::make('namespace')->badge()->sortable(),
                TextColumn::make('category.name')->badge()->sortable()->label('Cat'),
                TextColumn::make('user.name')->label('Autor')->sortable(),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'pending'   => 'warning',
                        'draft'     => 'gray',
                        'archived'  => 'danger',
                        default     => 'gray',
                    }),
                TextColumn::make('view_count')->sortable()->label('Views'),
                TextColumn::make('revision_count')->label('Rev')->sortable(),
                IconColumn::make('is_featured')->boolean(),
                IconColumn::make('is_redirect')->boolean()->label('↪'),
                TextColumn::make('updated_at')->dateTime('d.m.Y')->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(['draft' => 'Draft', 'pending' => 'Pending', 'published' => 'Published', 'archived' => 'Archived']),
                SelectFilter::make('namespace')
                    ->options(['main' => 'Main', 'help' => 'Help', 'template' => 'Template']),
                SelectFilter::make('wiki_category_id')
                    ->label('Category')
                    ->options(WikiCategory::pluck('name', 'id')),
            ])
            ->recordActions([
                Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (WikiArticle $record) => $record->status === 'pending')
                    ->action(function (WikiArticle $record) {
                        $record->update(['status' => 'published', 'published_at' => now(), 'approved_by' => auth()->id()]);
                        Notification::make()->title('Article published!')->success()->send();
                    }),
                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->url(fn (WikiArticle $record) => route('wiki.show', $record->slug))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListWikiArticles::route('/'),
            'create' => CreateWikiArticle::route('/create'),
            'edit'   => EditWikiArticle::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteKeyName(): string
    {
        return 'slug';
    }
}
