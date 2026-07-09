<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\Action;
use Throwable;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\TestserverModResource\Pages\ListTestserverMods;
use App\Filament\Resources\TestserverModResource\Pages\CreateTestserverMod;
use App\Filament\Resources\TestserverModResource\Pages\EditTestserverMod;
use App\Filament\Resources\TestserverModResource\Pages;
use App\Models\TestserverMod;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Http;

class TestserverModResource extends Resource
{
    protected static ?string $model = TestserverMod::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-puzzle-piece';
    protected static string | \UnitEnum | null $navigationGroup = 'Server Hosting';
    protected static ?string $navigationLabel = 'Testserver Mods';
    protected static ?int $navigationSort = 13;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identifikation')
                ->columns(2)
                ->schema([
                    TextInput::make('slug')
                        ->required()
                        ->maxLength(32)
                        ->unique(ignoreRecord: true)
                        ->helperText('Eindeutiger Identifier (z.B. nitmod, etpub). Wird intern verwendet.'),
                    TextInput::make('display_name')
                        ->required()
                        ->maxLength(64)
                        ->helperText('Wird im Public-Dropdown angezeigt'),
                    TextInput::make('fs_game_dir')
                        ->required()
                        ->maxLength(32)
                        ->helperText('Container-Ordner (fs_game Wert): legacy, nitmod, etpub, ...'),
                    TextInput::make('default_config_file')
                        ->required()
                        ->default('etl_server.cfg')
                        ->maxLength(64),
                    TextInput::make('short_description')
                        ->maxLength(255)
                        ->columnSpanFull(),
                ]),

            Section::make('FastDL-Anbindung')
                ->description('null/leer = Mod ist bereits im Container (z.B. legacy, etmain)')
                ->columns(1)
                ->schema([
                    TextInput::make('fastdl_archive_path')
                        ->label('FastDL Archive Pfad')
                        ->placeholder('mods/nitmod.zip')
                        ->helperText('Relativ zu https://dl.wolffiles.eu/et/ – leer wenn schon im Container'),
                    TextInput::make('fastdl_archive_sha256')
                        ->label('SHA256 Hash (optional)')
                        ->maxLength(64)
                        ->helperText('Wenn gesetzt: Integrity-Check vor Extract'),
                ]),

            Section::make('Sichtbarkeit & Sortierung')
                ->columns(3)
                ->schema([
                    Toggle::make('enabled')
                        ->label('Aktiviert')
                        ->default(true),
                    Toggle::make('show_on_public')
                        ->label('Public sichtbar')
                        ->default(true)
                        ->helperText('Im /testserver/launch Dropdown anzeigen?'),
                    TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Niedriger = weiter oben'),
                ]),

            Section::make('Egg-Kompatibilität')
                ->schema([
                    CheckboxList::make('compatible_egg_ids')
                        ->label('Kompatibel mit Eggs')
                        ->options([
                            17 => 'ET: Legacy (Wolffiles)',
                            18 => 'ET 2.60b Classic',
                            19 => 'RtCW Multiplayer',
                        ])
                        ->columns(3)
                        ->helperText('Leer = alle Eggs erlaubt'),
                ])
                ->collapsed(),

            Section::make('Notizen')
                ->schema([
                    Textarea::make('notes')
                        ->rows(3)
                        ->helperText('Interne Hinweise, z.B. spezielle Configs nötig'),
                ])
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('display_name')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('slug')
                    ->fontFamily('mono')
                    ->size('sm')
                    ->copyable(),
                TextColumn::make('fs_game_dir')
                    ->label('fs_game')
                    ->badge()
                    ->color('info'),
                TextColumn::make('fastdl_archive_path')
                    ->label('FastDL')
                    ->placeholder('— im Container —')
                    ->fontFamily('mono')
                    ->size('sm'),
                ToggleColumn::make('enabled'),
                ToggleColumn::make('show_on_public')
                    ->label('Public'),
                TextColumn::make('compatible_egg_ids')
                    ->label('Eggs')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : '—')
                    ->size('sm')
                    ->toggleable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                TernaryFilter::make('enabled'),
                TernaryFilter::make('show_on_public')->label('Public'),
            ])
            ->recordActions([
                Action::make('testFastdl')
                    ->label('Test FastDL')
                    ->icon('heroicon-o-cloud-arrow-down')
                    ->color('info')
                    ->visible(fn (TestserverMod $r) => !empty($r->fastdl_archive_path))
                    ->action(function (TestserverMod $record) {
                        $url = $record->fastdl_url;
                        try {
                            $response = Http::timeout(10)->head($url);
                            if ($response->successful()) {
                                $size = $response->header('Content-Length');
                                $sizeMb = $size ? round($size / 1024 / 1024, 1) . ' MB' : 'unbekannt';
                                Notification::make()
                                    ->title('✅ FastDL erreichbar')
                                    ->body("HTTP {$response->status()} · Size: {$sizeMb}")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('⚠ FastDL Problem')
                                    ->body("HTTP {$response->status()} · URL: {$url}")
                                    ->warning()
                                    ->send();
                            }
                        } catch (Throwable $e) {
                            Notification::make()
                                ->title('❌ FastDL nicht erreichbar')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                EditAction::make(),
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
            'index'  => ListTestserverMods::route('/'),
            'create' => CreateTestserverMod::route('/create'),
            'edit'   => EditTestserverMod::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::enabled()->count();
    }
}
