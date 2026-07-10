<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\ForumCategoryResource\Pages\ListForumCategories;
use App\Filament\Resources\ForumCategoryResource\Pages\CreateForumCategory;
use App\Filament\Resources\ForumCategoryResource\Pages\EditForumCategory;
use App\Models\ForumCategory;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ForumCategoryResource extends Resource
{
    protected static ?string $model = ForumCategory::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static string | \UnitEnum | null $navigationGroup = 'Forum';
    protected static ?string $navigationLabel = 'Kategorien';
    protected static ?string $modelLabel = 'Kategorie';
    protected static ?string $pluralModelLabel = 'Kategorien';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        $locales = array_filter(
            array_map('basename', glob(lang_path('*'), GLOB_ONLYDIR)),
            fn($d) => $d !== 'en'
        );

        return $schema->components([
            Section::make('Kategorie')->schema([
                Select::make('parent_id')
                    ->label('Übergeordnete Kategorie')
                    ->options(ForumCategory::root()->pluck('name', 'id'))
                    ->nullable()
                    ->placeholder('Keine (= Hauptkategorie)')
                    ->helperText('Leer lassen für eine Hauptkategorie.'),

                TextInput::make('name')
                    ->label('Name (Standard/EN)')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($set, $state) => $set('slug', Str::slug($state))),

                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Textarea::make('description')
                    ->label('Beschreibung (Standard/EN)')
                    ->rows(2)
                    ->maxLength(500),
            ])->columns(2),

            Section::make('Übersetzungen')->schema(
                collect($locales)->flatMap(function ($locale) {
                    return [
                        TextInput::make("name_translations.{$locale}")
                            ->label("Name ({$locale})")
                            ->maxLength(255),
                        TextInput::make("description_translations.{$locale}")
                            ->label("Beschreibung ({$locale})")
                            ->maxLength(500),
                    ];
                })->toArray()
            )->columns(2)->collapsible(),

            Section::make('Darstellung')->schema([
                TextInput::make('icon')
                    ->label('Font Awesome Icon')
                    ->placeholder('fas fa-gamepad')
                    ->helperText('z.B. fas fa-map, fas fa-server, fas fa-robot'),

                FileUpload::make('icon_image')
                    ->label('Eigenes Icon (Bild)')
                    
                    ->disk('s3')
                    ->directory('forum/icons')->visibility('public')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                    ->maxSize(512)
                    ->imagePreviewHeight('80')
                    ->helperText('Max 512KB. JPG, PNG, GIF, WebP. Wird bevorzugt wenn gesetzt.'),

                ColorPicker::make('color')
                    ->label('Farbe')
                    ->default('#3B82F6'),

                TextInput::make('sort_order')
                    ->label('Sortierung')
                    ->numeric()
                    ->default(0)
                    ->helperText('Niedrigere Zahl = weiter oben'),

                Toggle::make('is_locked')
                    ->label('Gesperrt (keine neuen Threads)')
                    ->default(false),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width('60px'),
                ColorColumn::make('color')
                    ->label('Farbe')
                    ->width('60px'),
                ImageColumn::make('icon_image')
                    ->label('Icon')
                    ->disk('s3')
                    ->circular()
                    ->width(32)
                    ->height(32)
                    ->defaultImageUrl(fn (ForumCategory $record) => null),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (ForumCategory $record) => $record->description),
                TextColumn::make('parent.name')
                    ->label('Übergeordnet')
                    ->placeholder('— Hauptkategorie —')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('threads_count')
                    ->label('Threads')
                    ->counts('threads')
                    ->sortable(),
                IconColumn::make('is_locked')
                    ->label('Gesperrt')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('danger')
                    ->falseColor('success'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (ForumCategory $record) {
                        foreach ($record->children as $child) {
                            $child->threads()->each(function ($thread) {
                                $thread->posts()->delete();
                                $thread->delete();
                            });
                        }
                        $record->threads()->each(function ($thread) {
                            $thread->posts()->delete();
                            $thread->delete();
                        });
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->paginated([10, 25, 50]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListForumCategories::route('/'),
            'create' => CreateForumCategory::route('/create'),
            'edit' => EditForumCategory::route('/{record}/edit'),
        ];
    }
}
