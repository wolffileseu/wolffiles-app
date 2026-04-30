<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Pages';


    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can('view_pages');
    }



    public static function form(Form $form): Form
    {
        // Helper: build a content-editor tuple (richtext/html/markdown) for a given field path
        $contentEditors = function (string $field, string $label): array {
            return [
                Forms\Components\RichEditor::make($field)
                    ->label($label)
                    ->columnSpanFull()
                    ->visible(fn (callable $get) => ($get('content_type') ?? 'richtext') === 'richtext'),
                Forms\Components\Textarea::make($field)
                    ->label($label . ' (HTML)')
                    ->columnSpanFull()
                    ->rows(20)
                    ->extraAttributes(['style' => 'font-family: monospace; font-size: 13px;'])
                    ->visible(fn (callable $get) => $get('content_type') === 'html'),
                Forms\Components\MarkdownEditor::make($field)
                    ->label($label . ' (Markdown)')
                    ->columnSpanFull()
                    ->visible(fn (callable $get) => $get('content_type') === 'markdown'),
            ];
        };

        return $form->schema([
            Forms\Components\Placeholder::make('tools_hint')
                ->label('')
                ->content(new \Illuminate\Support\HtmlString(
                    '<div style="background:#1f2937;border-left:3px solid #f59e0b;padding:12px 16px;border-radius:6px;color:#d1d5db;font-size:13px;line-height:1.5;">'
                    . '<strong style="color:#f59e0b;">ℹ️ Hinweis:</strong> '
                    . 'Diese Seite verwaltet <strong>statische Inhalte</strong> wie Impressum, Datenschutz oder Hilfetexte. '
                    . 'Tools wie der <em>ET Nickname Generator</em> oder der <em>Omni-Bot Browser</em> sind hartcodiert und werden im Code (Blade-Templates) gepflegt. '
                    . 'Übersetzungen für Tools laufen über den <strong>TranslationManager</strong> (lang/&#123;locale&#125;/messages.php).'
                    . '</div>'
                ))
                ->columnSpanFull(),

            Forms\Components\TextInput::make('slug')
                ->maxLength(255)
                ->hint('Leer lassen für automatische Generierung aus DE-Titel. Reservierte Slugs (Tools/System) sind nicht erlaubt.')
                ->unique(ignoreRecord: true)
                ->rules([
                    function () {
                        return function (string $attribute, $value, \Closure $fail) {
                            $reserved = [
                                'nickname-generator',
                                'omni-bot',
                                'omnibot',
                                'tools',
                                'admin',
                                'api',
                                'login',
                                'register',
                                'logout',
                                'dashboard',
                                'files',
                                'news',
                                'contact',
                                'sitemap',
                                'statistics',
                                'rss',
                                'upload',
                                'wiki',
                                'forum',
                                'profile',
                                'user',
                            ];
                            if (in_array(strtolower((string) $value), $reserved, true)) {
                                $fail('Der Slug "' . $value . '" ist reserviert (Tool/System) und kann nicht für eine Page verwendet werden.');
                            }
                        };
                    },
                ])
                ->columnSpanFull(),

            Forms\Components\Grid::make(2)->schema([
                Forms\Components\Select::make('type')
                    ->options([
                        'page' => 'Page',
                        'legal' => 'Legal (Impressum, Privacy)',
                        'info' => 'Info',
                    ])
                    ->default('page'),

                Forms\Components\Select::make('content_type')
                    ->label('Content Type')
                    ->options([
                        'richtext' => 'Rich Text Editor',
                        'html' => 'HTML Code',
                        'markdown' => 'Markdown',
                    ])
                    ->default('richtext')
                    ->live(),
            ]),

            Forms\Components\Tabs::make('translations')
                ->columnSpanFull()
                ->tabs([
                    Forms\Components\Tabs\Tab::make('🇩🇪 Deutsch (Standard)')
                        ->schema(array_merge([
                            Forms\Components\TextInput::make('title')
                                ->label('Titel')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, callable $set, ?Page $record) {
                                    if (!$record) {
                                        $set('slug', \Illuminate\Support\Str::slug($state));
                                    }
                                }),
                        ], $contentEditors('content', 'Inhalt'))),

                    Forms\Components\Tabs\Tab::make('🇬🇧 English')
                        ->schema(array_merge([
                            Forms\Components\TextInput::make('title_translations.en')
                                ->label('Title (EN)')->maxLength(255),
                        ], $contentEditors('content_translations.en', 'Content (EN)'))),

                    Forms\Components\Tabs\Tab::make('🇫🇷 Français')
                        ->schema(array_merge([
                            Forms\Components\TextInput::make('title_translations.fr')
                                ->label('Titre (FR)')->maxLength(255),
                        ], $contentEditors('content_translations.fr', 'Contenu (FR)'))),

                    Forms\Components\Tabs\Tab::make('🇳🇱 Nederlands')
                        ->schema(array_merge([
                            Forms\Components\TextInput::make('title_translations.nl')
                                ->label('Titel (NL)')->maxLength(255),
                        ], $contentEditors('content_translations.nl', 'Inhoud (NL)'))),

                    Forms\Components\Tabs\Tab::make('🇵🇱 Polski')
                        ->schema(array_merge([
                            Forms\Components\TextInput::make('title_translations.pl')
                                ->label('Tytuł (PL)')->maxLength(255),
                        ], $contentEditors('content_translations.pl', 'Treść (PL)'))),

                    Forms\Components\Tabs\Tab::make('🇹🇷 Türkçe')
                        ->schema(array_merge([
                            Forms\Components\TextInput::make('title_translations.tr')
                                ->label('Başlık (TR)')->maxLength(255),
                        ], $contentEditors('content_translations.tr', 'İçerik (TR)'))),
                ]),

            Forms\Components\Select::make('template')
                ->options([
                    'default' => 'Default',
                    'full-width' => 'Full Width',
                    'sidebar' => 'With Sidebar',
                ])
                ->default('default'),

            Forms\Components\FileUpload::make('pdf_path')
                ->disk('s3')
                ->directory('pages/pdf')
                ->label('PDF Attachment')
                ->acceptedFileTypes(['application/pdf'])
                ->maxSize(20480)
                ->hint('Upload a PDF file (max 20MB). Will be shown as download link on the page.'),

            Forms\Components\Grid::make(2)->schema([
                Forms\Components\Toggle::make('is_published')->default(false),
                Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->searchable()->color('gray'),
                Tables\Columns\TextColumn::make('type')->badge()->color(fn (string $state): string => match ($state) {
                    'legal' => 'danger',
                    'info' => 'info',
                    default => 'gray',
                }),
                Tables\Columns\TextColumn::make('content_type')
                    ->label('Format')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'html' => 'warning',
                        'markdown' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state) => ucfirst($state ?? 'richtext')),
                Tables\Columns\IconColumn::make('is_published')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime('d.m.Y')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
