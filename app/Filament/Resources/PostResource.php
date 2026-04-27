<?php
namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Clan;
use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;
    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    protected static ?string $navigationGroup = 'Content';

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can('view_posts');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Allgemein')->schema([
                Forms\Components\Select::make('type')
                    ->label('Typ')
                    ->options(Post::TYPES)
                    ->default(Post::TYPE_NEWS)
                    ->required()
                    ->live(),
                Forms\Components\Select::make('clan_id')
                    ->label('Clan')
                    ->options(Clan::where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->nullable()
                    ->placeholder('Kein Clan (Wolffiles News)')
                    ->visible(fn(Get $get) => $get('type') !== Post::TYPE_NEWS || true),
            ])->columns(2),

            Forms\Components\Section::make('Inhalt & Übersetzungen')->schema([
                Forms\Components\TextInput::make('slug')
                    ->maxLength(255)
                    ->hint('Leer lassen für automatische Generierung aus DE-Titel')
                    ->unique(ignoreRecord: true)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('excerpt')
                    ->label('Kurzbeschreibung (DE)')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Tabs::make('translations')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('🇩🇪 Deutsch (Standard)')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Titel')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\RichEditor::make('content')
                                    ->label('Inhalt')
                                    ->required(),
                            ]),
                        Forms\Components\Tabs\Tab::make('🇬🇧 English')
                            ->schema([
                                Forms\Components\TextInput::make('title_translations.en')
                                    ->label('Title (EN)')->maxLength(255),
                                Forms\Components\RichEditor::make('content_translations.en')
                                    ->label('Content (EN)'),
                            ]),
                        Forms\Components\Tabs\Tab::make('🇫🇷 Français')
                            ->schema([
                                Forms\Components\TextInput::make('title_translations.fr')
                                    ->label('Titre (FR)')->maxLength(255),
                                Forms\Components\RichEditor::make('content_translations.fr')
                                    ->label('Contenu (FR)'),
                            ]),
                        Forms\Components\Tabs\Tab::make('🇳🇱 Nederlands')
                            ->schema([
                                Forms\Components\TextInput::make('title_translations.nl')
                                    ->label('Titel (NL)')->maxLength(255),
                                Forms\Components\RichEditor::make('content_translations.nl')
                                    ->label('Inhoud (NL)'),
                            ]),
                        Forms\Components\Tabs\Tab::make('🇵🇱 Polski')
                            ->schema([
                                Forms\Components\TextInput::make('title_translations.pl')
                                    ->label('Tytuł (PL)')->maxLength(255),
                                Forms\Components\RichEditor::make('content_translations.pl')
                                    ->label('Treść (PL)'),
                            ]),
                        Forms\Components\Tabs\Tab::make('🇹🇷 Türkçe')
                            ->schema([
                                Forms\Components\TextInput::make('title_translations.tr')
                                    ->label('Başlık (TR)')->maxLength(255),
                                Forms\Components\RichEditor::make('content_translations.tr')
                                    ->label('İçerik (TR)'),
                            ]),
                    ]),
                Forms\Components\FileUpload::make('featured_image')
                    ->disk('s3')
                    ->directory('posts/images')
                    ->image()
                    ->imageResizeMode('cover')
                    ->imageCropAspectRatio('16:9')
                    ->imageResizeTargetWidth('1200')
                    ->imageResizeTargetHeight('675')
                    ->label('Beitragsbild')
                    ->columnSpanFull(),
            ])->columns(1),

            // Event Felder
            Forms\Components\Section::make('Event Details')
                ->schema([
                    Forms\Components\DateTimePicker::make('event_date')
                        ->label('Event Datum & Uhrzeit')
                        ->required(),
                    Forms\Components\TextInput::make('event_location')
                        ->label('Ort / Server')
                        ->maxLength(255),
                ])
                ->columns(2)
                ->visible(fn(Get $get) => $get('type') === Post::TYPE_EVENT),

            // Match Felder
            Forms\Components\Section::make('Match Details')
                ->schema([
                    Forms\Components\TextInput::make('match_opponent')
                        ->label('Gegner-Clan')
                        ->required()
                        ->maxLength(100),
                    Forms\Components\TextInput::make('match_result')
                        ->label('Ergebnis (z.B. 2:1)')
                        ->maxLength(50),
                    Forms\Components\TextInput::make('match_map')
                        ->label('Map')
                        ->maxLength(100),
                    Forms\Components\DateTimePicker::make('event_date')
                        ->label('Match Datum'),
                ])
                ->columns(2)
                ->visible(fn(Get $get) => $get('type') === Post::TYPE_MATCH),

            // Rekrutierung Felder
            Forms\Components\Section::make('Rekrutierung Details')
                ->schema([
                    Forms\Components\Repeater::make('recruitment_requirements')
                        ->label('Anforderungen')
                        ->schema([
                            Forms\Components\TextInput::make('requirement')
                                ->label('Anforderung')
                                ->required(),
                        ])
                        ->columnSpanFull(),
                ])
                ->visible(fn(Get $get) => $get('type') === Post::TYPE_RECRUITMENT),

            Forms\Components\Section::make('Veröffentlichung')->schema([
                Forms\Components\Toggle::make('is_published')->label('Veröffentlicht')->default(false),
                Forms\Components\Toggle::make('is_pinned')->label('Angepinnt')->default(false),
                Forms\Components\DateTimePicker::make('published_at')->label('Veröffentlichungsdatum')->default(now()),
            ])->columns(3),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('featured_image')
                    ->disk('s3')
                    ->label('Bild')
                    ->width(60)
                    ->height(40),
                Tables\Columns\TextColumn::make('type')
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
                Tables\Columns\TextColumn::make('clan.name')
                    ->label('Clan')
                    ->placeholder('Wolffiles')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_published')
                    ->label('Veröffentlicht')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_pinned')
                    ->label('Angepinnt')
                    ->boolean(),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('Datum')
                    ->dateTime('d.m.Y'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Typ')
                    ->options(Post::TYPES),
                Tables\Filters\SelectFilter::make('clan_id')
                    ->label('Clan')
                    ->options(Clan::pluck('name', 'id')),
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Veröffentlicht'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit'   => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
