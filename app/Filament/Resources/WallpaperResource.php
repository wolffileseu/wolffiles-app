<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WallpaperResource\Pages;
use App\Models\Wallpaper;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WallpaperResource extends Resource
{
    protected static ?string $model = Wallpaper::class;
    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Wallpapers';
    protected static ?int $navigationSort = 60;


    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Image')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('e.g. Adlernest Sunset'),
                    Forms\Components\FileUpload::make('image_path')
                        ->label('Wallpaper Image')
                        ->required()
                        ->disk('s3')
                        ->directory('wallpapers')
                        ->image()
                        ->imageEditor()
                        ->imageEditorAspectRatios(['16:9', '21:9', null])
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(15360)
                        ->imagePreviewHeight('200')
                        ->helperText('Recommended: 1920×1080 or larger. Max 15 MB. JPG/PNG/WebP.'),
                ])->columns(1),

            Forms\Components\Section::make('Display')
                ->schema([
                    Forms\Components\CheckboxList::make('display_areas')
                        ->label('Show on these areas')
                        ->options(Wallpaper::AREAS)
                        ->required()
                        ->columns(2)
                        ->helperText('Pick "Layout-wide" to show on every page.'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->inline(false),
                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Order in slideshow (lower = first).'),
                ])->columns(2),

            Forms\Components\Section::make('Overlay')
                ->description('Tint and blur applied on top of the image so content stays readable.')
                ->schema([
                    Forms\Components\ColorPicker::make('overlay_color')
                        ->default('#111827')
                        ->helperText('Default #111827 = dark gray (matches site theme).'),
                    Forms\Components\TextInput::make('overlay_opacity')
                        ->label('Overlay Opacity (%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->default(70)
                        ->suffix('%')
                        ->helperText('0 = no overlay, 100 = fully opaque'),
                    Forms\Components\TextInput::make('overlay_blur')
                        ->label('Image Blur (px)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(20)
                        ->default(0)
                        ->suffix('px')
                        ->helperText('0 = sharp, up to 20px blur'),
                ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('Preview')
                    ->disk('s3')
                    ->height(50),
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('display_areas')
                    ->label('Areas')
                    ->badge()
                    ->formatStateUsing(fn ($state) => is_array($state) ? array_map(fn ($a) => Wallpaper::AREAS[$a] ?? $a, $state) : []),
                Tables\Columns\ColorColumn::make('overlay_color')->label('Overlay'),
                Tables\Columns\TextColumn::make('overlay_opacity')->label('Opacity')->suffix('%'),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
                Tables\Columns\TextColumn::make('sort_order')->label('Order')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
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
            'index' => Pages\ListWallpapers::route('/'),
            'create' => Pages\CreateWallpaper::route('/create'),
            'edit' => Pages\EditWallpaper::route('/{record}/edit'),
        ];
    }
}
