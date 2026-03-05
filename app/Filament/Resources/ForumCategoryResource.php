<?php

namespace App\Filament\Resources;

use App\Models\ForumCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ForumCategoryResource extends Resource
{
    protected static ?string $model = ForumCategory::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = 'Forum';
    protected static ?string $navigationLabel = 'Kategorien';
    protected static ?string $modelLabel = 'Kategorie';
    protected static ?string $pluralModelLabel = 'Kategorien';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        $locales = array_filter(
            array_map('basename', glob(lang_path('*'), GLOB_ONLYDIR)),
            fn($d) => $d !== 'en'
        );

        return $form->schema([
            Forms\Components\Section::make('Kategorie')->schema([
                Forms\Components\Select::make('parent_id')
                    ->label('Übergeordnete Kategorie')
                    ->options(ForumCategory::root()->pluck('name', 'id'))
                    ->nullable()
                    ->placeholder('Keine (= Hauptkategorie)')
                    ->helperText('Leer lassen für eine Hauptkategorie.'),

                Forms\Components\TextInput::make('name')
                    ->label('Name (Standard/EN)')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($set, $state) => $set('slug', Str::slug($state))),

                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Forms\Components\Textarea::make('description')
                    ->label('Beschreibung (Standard/EN)')
                    ->rows(2)
                    ->maxLength(500),
            ])->columns(2),

            Forms\Components\Section::make('Übersetzungen')->schema(
                collect($locales)->flatMap(function ($locale) {
                    return [
                        Forms\Components\TextInput::make("name_translations.{$locale}")
                            ->label("Name ({$locale})")
                            ->maxLength(255),
                        Forms\Components\TextInput::make("description_translations.{$locale}")
                            ->label("Beschreibung ({$locale})")
                            ->maxLength(500),
                    ];
                })->toArray()
            )->columns(2)->collapsible(),

            Forms\Components\Section::make('Darstellung')->schema([
                Forms\Components\TextInput::make('icon')
                    ->label('Font Awesome Icon')
                    ->placeholder('fas fa-gamepad')
                    ->helperText('z.B. fas fa-map, fas fa-server, fas fa-robot'),

                Forms\Components\FileUpload::make('icon_image')
                    ->label('Eigenes Icon (Bild)')
                    ->image()
                    ->disk('s3')
                    ->directory('forum/icons')
                    ->maxSize(512)
                    ->helperText('Max 512KB. Wird bevorzugt wenn gesetzt.'),

                Forms\Components\ColorPicker::make('color')
                    ->label('Farbe')
                    ->default('#3B82F6'),

                Forms\Components\TextInput::make('sort_order')
                    ->label('Sortierung')
                    ->numeric()
                    ->default(0)
                    ->helperText('Niedrigere Zahl = weiter oben'),

                Forms\Components\Toggle::make('is_locked')
                    ->label('Gesperrt (keine neuen Threads)')
                    ->default(false),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width('60px'),
                Tables\Columns\ColorColumn::make('color')
                    ->label('Farbe')
                    ->width('60px'),
                Tables\Columns\ImageColumn::make('icon_image')
                    ->label('Icon')
                    ->disk('s3')
                    ->circular()
                    ->width(32)
                    ->height(32)
                    ->defaultImageUrl(fn (ForumCategory $record) => null),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (ForumCategory $record) => $record->description),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Übergeordnet')
                    ->placeholder('— Hauptkategorie —')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('threads_count')
                    ->label('Threads')
                    ->counts('threads')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_locked')
                    ->label('Gesperrt')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('danger')
                    ->falseColor('success'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
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
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->paginated([10, 25, 50]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ForumCategoryResource\Pages\ListForumCategories::route('/'),
            'create' => ForumCategoryResource\Pages\CreateForumCategory::route('/create'),
            'edit' => ForumCategoryResource\Pages\EditForumCategory::route('/{record}/edit'),
        ];
    }
}
