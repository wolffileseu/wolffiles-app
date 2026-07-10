<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\TextInput;
use Closure;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Support\Str;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\PageResource\Pages\ListPages;
use App\Filament\Resources\PageResource\Pages\CreatePage;
use App\Filament\Resources\PageResource\Pages\EditPage;
use App\Filament\Resources\PageResource\Pages;
use App\Models\Page;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';
    protected static string | \UnitEnum | null $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Pages';





    public static function form(Schema $schema): Schema
    {
        // Helper: build a content-editor tuple (richtext/html/markdown) for a given field path
        $contentEditors = function (string $field, string $label): array {
            return [
                RichEditor::make($field)
                    ->label($label)
                    ->columnSpanFull()
                    ->visible(fn (Get $get) => ($get('content_type') ?? 'richtext') === 'richtext'),
                Textarea::make($field)
                    ->label($label . ' (HTML)')
                    ->columnSpanFull()
                    ->rows(20)
                    ->extraAttributes(['style' => 'font-family: monospace; font-size: 13px;'])
                    ->visible(fn (Get $get) => $get('content_type') === 'html'),
                MarkdownEditor::make($field)
                    ->label($label . ' (Markdown)')
                    ->columnSpanFull()
                    ->visible(fn (Get $get) => $get('content_type') === 'markdown'),
            ];
        };

        return $schema->components([
            Placeholder::make('tools_hint')
                ->label('')
                ->content(new HtmlString(
                    '<div style="background:#1f2937;border-left:3px solid #f59e0b;padding:12px 16px;border-radius:6px;color:#d1d5db;font-size:13px;line-height:1.5;">'
                    . '<strong style="color:#f59e0b;">ℹ️ Hinweis:</strong> '
                    . 'Diese Seite verwaltet <strong>statische Inhalte</strong> wie Impressum, Datenschutz oder Hilfetexte. '
                    . 'Tools wie der <em>ET Nickname Generator</em> oder der <em>Omni-Bot Browser</em> sind hartcodiert und werden im Code (Blade-Templates) gepflegt. '
                    . 'Übersetzungen für Tools laufen über den <strong>TranslationManager</strong> (lang/&#123;locale&#125;/messages.php).'
                    . '</div>'
                ))
                ->columnSpanFull(),

            TextInput::make('slug')
                ->maxLength(255)
                ->hint('Leer lassen für automatische Generierung aus DE-Titel. Reservierte Slugs (Tools/System) sind nicht erlaubt.')
                ->unique(ignoreRecord: true)
                ->rules([
                    function () {
                        return function (string $attribute, $value, Closure $fail) {
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

            Grid::make(2)->schema([
                Select::make('type')
                    ->options([
                        'page' => 'Page',
                        'legal' => 'Legal (Impressum, Privacy)',
                        'info' => 'Info',
                    ])
                    ->default('page'),

                Select::make('content_type')
                    ->label('Content Type')
                    ->options([
                        'richtext' => 'Rich Text Editor',
                        'html' => 'HTML Code',
                        'markdown' => 'Markdown',
                    ])
                    ->default('richtext')
                    ->live(),
            ]),

            Tabs::make('translations')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('🇩🇪 Deutsch (Standard)')
                        ->schema(array_merge([
                            TextInput::make('title')
                                ->label('Titel')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Set $set, ?Page $record) {
                                    if (!$record) {
                                        $set('slug', Str::slug($state));
                                    }
                                }),
                        ], $contentEditors('content', 'Inhalt'))),

                    Tab::make('🇬🇧 English')
                        ->schema(array_merge([
                            TextInput::make('title_translations.en')
                                ->label('Title (EN)')->maxLength(255),
                        ], $contentEditors('content_translations.en', 'Content (EN)'))),

                    Tab::make('🇫🇷 Français')
                        ->schema(array_merge([
                            TextInput::make('title_translations.fr')
                                ->label('Titre (FR)')->maxLength(255),
                        ], $contentEditors('content_translations.fr', 'Contenu (FR)'))),

                    Tab::make('🇳🇱 Nederlands')
                        ->schema(array_merge([
                            TextInput::make('title_translations.nl')
                                ->label('Titel (NL)')->maxLength(255),
                        ], $contentEditors('content_translations.nl', 'Inhoud (NL)'))),

                    Tab::make('🇵🇱 Polski')
                        ->schema(array_merge([
                            TextInput::make('title_translations.pl')
                                ->label('Tytuł (PL)')->maxLength(255),
                        ], $contentEditors('content_translations.pl', 'Treść (PL)'))),

                    Tab::make('🇹🇷 Türkçe')
                        ->schema(array_merge([
                            TextInput::make('title_translations.tr')
                                ->label('Başlık (TR)')->maxLength(255),
                        ], $contentEditors('content_translations.tr', 'İçerik (TR)'))),
                ]),

            Select::make('template')
                ->options([
                    'default' => 'Default',
                    'full-width' => 'Full Width',
                    'sidebar' => 'With Sidebar',
                ])
                ->default('default'),

            FileUpload::make('pdf_path')
                ->disk('s3')
                ->directory('pages/pdf')->visibility('public')
                ->label('PDF Attachment')
                ->acceptedFileTypes(['application/pdf'])
                ->maxSize(20480)
                ->hint('Upload a PDF file (max 20MB). Will be shown as download link on the page.'),

            Grid::make(2)->schema([
                Toggle::make('is_published')->default(false),
                TextInput::make('sort_order')->numeric()->default(0),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('slug')->searchable()->color('gray'),
                TextColumn::make('type')->badge()->color(fn (string $state): string => match ($state) {
                    'legal' => 'danger',
                    'info' => 'info',
                    default => 'gray',
                }),
                TextColumn::make('content_type')
                    ->label('Format')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'html' => 'warning',
                        'markdown' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state) => ucfirst($state ?? 'richtext')),
                IconColumn::make('is_published')->boolean(),
                TextColumn::make('sort_order')->sortable(),
                TextColumn::make('updated_at')->dateTime('d.m.Y')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
