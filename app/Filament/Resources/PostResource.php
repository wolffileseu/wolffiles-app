<?php
namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\PostResource\Pages\ListPosts;
use App\Filament\Resources\PostResource\Pages\CreatePost;
use App\Filament\Resources\PostResource\Pages\EditPost;
use App\Filament\Resources\PostResource\Pages;
use App\Models\Clan;
use App\Models\Post;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-newspaper';
    protected static string | \UnitEnum | null $navigationGroup = 'Content';


    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Allgemein')->schema([
                Select::make('type')
                    ->label('Typ')
                    ->options(Post::TYPES)
                    ->default(Post::TYPE_NEWS)
                    ->required()
                    ->live(),
                Select::make('clan_id')
                    ->label('Clan')
                    ->options(Clan::where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->nullable()
                    ->placeholder('Kein Clan (Wolffiles News)')
                    ->visible(fn(Get $get) => $get('type') !== Post::TYPE_NEWS || true),
            ])->columns(2),

            Section::make('Inhalt & Übersetzungen')->schema([
                TextInput::make('slug')
                    ->maxLength(255)
                    ->hint('Leer lassen für automatische Generierung aus DE-Titel')
                    ->unique(ignoreRecord: true)
                    ->columnSpanFull(),
                Textarea::make('excerpt')
                    ->label('Kurzbeschreibung (DE)')
                    ->rows(3)
                    ->columnSpanFull(),
                Tabs::make('translations')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('🇩🇪 Deutsch (Standard)')
                            ->schema([
                                TextInput::make('title')
                                    ->label('Titel')
                                    ->required()
                                    ->maxLength(255),
                                RichEditor::make('content')
                                    ->label('Inhalt')
                                    ->required(),
                            ]),
                        Tab::make('🇬🇧 English')
                            ->schema([
                                TextInput::make('title_translations.en')
                                    ->label('Title (EN)')->maxLength(255),
                                RichEditor::make('content_translations.en')
                                    ->label('Content (EN)'),
                            ]),
                        Tab::make('🇫🇷 Français')
                            ->schema([
                                TextInput::make('title_translations.fr')
                                    ->label('Titre (FR)')->maxLength(255),
                                RichEditor::make('content_translations.fr')
                                    ->label('Contenu (FR)'),
                            ]),
                        Tab::make('🇳🇱 Nederlands')
                            ->schema([
                                TextInput::make('title_translations.nl')
                                    ->label('Titel (NL)')->maxLength(255),
                                RichEditor::make('content_translations.nl')
                                    ->label('Inhoud (NL)'),
                            ]),
                        Tab::make('🇵🇱 Polski')
                            ->schema([
                                TextInput::make('title_translations.pl')
                                    ->label('Tytuł (PL)')->maxLength(255),
                                RichEditor::make('content_translations.pl')
                                    ->label('Treść (PL)'),
                            ]),
                        Tab::make('🇹🇷 Türkçe')
                            ->schema([
                                TextInput::make('title_translations.tr')
                                    ->label('Başlık (TR)')->maxLength(255),
                                RichEditor::make('content_translations.tr')
                                    ->label('İçerik (TR)'),
                            ]),
                    ]),
                FileUpload::make('featured_image')
                    ->disk('s3')
                    ->directory('posts/images')->visibility('public')
                    ->image()
                    ->imageResizeMode('cover')
                    ->imageCropAspectRatio('16:9')
                    ->imageResizeTargetWidth('1200')
                    ->imageResizeTargetHeight('675')
                    ->label('Beitragsbild')
                    ->columnSpanFull(),
            ])->columns(1),

            // Event Felder
            Section::make('Event Details')
                ->schema([
                    DateTimePicker::make('event_date')
                        ->label('Event Datum & Uhrzeit')
                        ->required(),
                    TextInput::make('event_location')
                        ->label('Ort / Server')
                        ->maxLength(255),
                ])
                ->columns(2)
                ->visible(fn(Get $get) => $get('type') === Post::TYPE_EVENT),

            // Match Felder
            Section::make('Match Details')
                ->schema([
                    TextInput::make('match_opponent')
                        ->label('Gegner-Clan')
                        ->required()
                        ->maxLength(100),
                    TextInput::make('match_result')
                        ->label('Ergebnis (z.B. 2:1)')
                        ->maxLength(50),
                    TextInput::make('match_map')
                        ->label('Map')
                        ->maxLength(100),
                    DateTimePicker::make('event_date')
                        ->label('Match Datum'),
                ])
                ->columns(2)
                ->visible(fn(Get $get) => $get('type') === Post::TYPE_MATCH),

            // Rekrutierung Felder
            Section::make('Rekrutierung Details')
                ->schema([
                    Repeater::make('recruitment_requirements')
                        ->label('Anforderungen')
                        ->schema([
                            TextInput::make('requirement')
                                ->label('Anforderung')
                                ->required(),
                        ])
                        ->columnSpanFull(),
                ])
                ->visible(fn(Get $get) => $get('type') === Post::TYPE_RECRUITMENT),

            Section::make('Veröffentlichung')->schema([
                Toggle::make('is_published')->label('Veröffentlicht')->default(false),
                Toggle::make('is_pinned')->label('Angepinnt')->default(false),
                DateTimePicker::make('published_at')->label('Veröffentlichungsdatum')->default(now()),
            ])->columns(3),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('featured_image')
                    ->disk('s3')
                    ->label('Bild')
                    ->width(60)
                    ->height(40),
                TextColumn::make('type')
                    ->label('Typ')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        Post::TYPE_NEWS        => 'info',
                        Post::TYPE_EVENT       => 'success',
                        Post::TYPE_MATCH       => 'warning',
                        Post::TYPE_RECRUITMENT => 'danger',
                        default                => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => Post::TYPES[$state] ?? $state),
                TextColumn::make('clan.name')
                    ->label('Clan')
                    ->placeholder('Wolffiles')
                    ->searchable(),
                TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_published')
                    ->label('Veröffentlicht')
                    ->boolean(),
                IconColumn::make('is_pinned')
                    ->label('Angepinnt')
                    ->boolean(),
                TextColumn::make('published_at')
                    ->label('Datum')
                    ->dateTime('d.m.Y'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Typ')
                    ->options(Post::TYPES),
                SelectFilter::make('clan_id')
                    ->label('Clan')
                    ->options(Clan::pluck('name', 'id')),
                TernaryFilter::make('is_published')
                    ->label('Veröffentlicht'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit'   => EditPost::route('/{record}/edit'),
        ];
    }
}
