<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ColorPicker;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\WallpaperResource\Pages\ListWallpapers;
use App\Filament\Resources\WallpaperResource\Pages\CreateWallpaper;
use App\Filament\Resources\WallpaperResource\Pages\EditWallpaper;
use App\Filament\Resources\WallpaperResource\Pages;
use App\Models\Wallpaper;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WallpaperResource extends Resource
{
    protected static ?string $model = Wallpaper::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-photo';
    protected static string | \UnitEnum | null $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Wallpapers';
    protected static ?int $navigationSort = 60;


    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Image')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('e.g. Adlernest Sunset'),
                    FileUpload::make('image_path')
                        ->label('Wallpaper Image')
                        ->required()
                        ->disk('s3')
                        ->directory('wallpapers')->visibility('public')
                        ->image()
                        ->imageEditor()
                        ->imageEditorAspectRatios(['16:9', '21:9', null])
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(15360)
                        ->imagePreviewHeight('200')
                        ->helperText('Recommended: 1920×1080 or larger. Max 15 MB. JPG/PNG/WebP.'),
                ])->columns(1),

            Section::make('Display')
                ->schema([
                    CheckboxList::make('display_areas')
                        ->label('Show on these areas')
                        ->options(Wallpaper::AREAS)
                        ->required()
                        ->columns(2)
                        ->helperText('Pick "Layout-wide" to show on every page.'),
                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->inline(false),
                    TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Order in slideshow (lower = first).'),
                ])->columns(2),

            Section::make('Overlay')
                ->description('Tint and blur applied on top of the image so content stays readable.')
                ->schema([
                    ColorPicker::make('overlay_color')
                        ->default('#111827')
                        ->helperText('Default #111827 = dark gray (matches site theme).'),
                    TextInput::make('overlay_opacity')
                        ->label('Overlay Opacity (%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->default(70)
                        ->suffix('%')
                        ->helperText('0 = no overlay, 100 = fully opaque'),
                    TextInput::make('overlay_blur')
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
                ImageColumn::make('image_path')
                    ->label('Preview')
                    ->disk('s3')
                    ->height(50),
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('display_areas')
                    ->label('Areas')
                    ->badge()
                    ->formatStateUsing(fn ($state) => is_array($state) ? array_map(fn ($a) => Wallpaper::AREAS[$a] ?? $a, $state) : []),
                ColorColumn::make('overlay_color')->label('Overlay'),
                TextColumn::make('overlay_opacity')->label('Opacity')->suffix('%'),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('sort_order')->label('Order')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
            ])
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
            'index' => ListWallpapers::route('/'),
            'create' => CreateWallpaper::route('/create'),
            'edit' => EditWallpaper::route('/{record}/edit'),
        ];
    }
}
