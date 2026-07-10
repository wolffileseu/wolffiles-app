<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TagsInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\ServerProductResource\Pages\ListServerProducts;
use App\Filament\Resources\ServerProductResource\Pages\CreateServerProduct;
use App\Filament\Resources\ServerProductResource\Pages\EditServerProduct;
use App\Filament\Resources\ServerProductResource\Pages;
use App\Models\ServerProduct;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServerProductResource extends Resource
{
    protected static ?string $model = ServerProduct::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cube';
    protected static string | \UnitEnum | null $navigationGroup = 'Server Hosting';
    protected static ?string $navigationLabel = 'Products';
    protected static ?int $navigationSort = 1;


    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Produkt Info')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('slug')->required()->unique(ignoreRecord: true),
                    Select::make('game')
                        ->options(['et' => 'ET 2.60b', 'etl' => 'ET: Legacy', 'rtcw' => 'RtCW'])
                        ->required(),
                    Toggle::make('is_active')->default(true),
                    Textarea::make('description')->columnSpanFull(),
                    TextInput::make('sort_order')->numeric()->default(0),
                ]),

            Section::make('Slot Konfiguration')
                ->columns(3)
                ->schema([
                    TextInput::make('min_slots')->numeric()->required()->default(2)->minValue(1),
                    TextInput::make('max_slots')->numeric()->required()->default(64)->maxValue(128),
                    TextInput::make('slots')->numeric()->label('Default Slots')->default(24),
                ]),

            Section::make('Preis pro Slot')
                ->description('Preis pro Slot pro Zeitraum. Endpreis = Slots × Preis pro Slot')
                ->columns(4)
                ->schema([
                    TextInput::make('price_per_slot_daily')->numeric()->prefix('€')->step(0.01)->required(),
                    TextInput::make('price_per_slot_weekly')->numeric()->prefix('€')->step(0.01)->required(),
                    TextInput::make('price_per_slot_monthly')->numeric()->prefix('€')->step(0.01)->required(),
                    TextInput::make('price_per_slot_quarterly')->numeric()->prefix('€')->step(0.01)->required(),
                ]),

            Section::make('Beispielpreise (feste Pakete, für Anzeige)')
                ->columns(4)
                ->collapsed()
                ->schema([
                    TextInput::make('price_daily')->numeric()->prefix('€')->step(0.01),
                    TextInput::make('price_weekly')->numeric()->prefix('€')->step(0.01),
                    TextInput::make('price_monthly')->numeric()->prefix('€')->step(0.01),
                    TextInput::make('price_quarterly')->numeric()->prefix('€')->step(0.01),
                ]),

            Section::make('Ressourcen pro Slot')
                ->description('Base + (Slots × Per Slot) = Total')
                ->columns(3)
                ->schema([
                    TextInput::make('base_memory_mb')->numeric()->suffix('MB')->label('Base RAM')->required(),
                    TextInput::make('memory_per_slot_mb')->numeric()->suffix('MB')->label('RAM/Slot')->required(),
                    TextInput::make('memory_mb')->numeric()->suffix('MB')->label('Default Total RAM'),
                    TextInput::make('cpu_per_slot_percent')->numeric()->suffix('%')->label('CPU/Slot')->required(),
                    TextInput::make('cpu_percent')->numeric()->suffix('%')->label('Default Total CPU'),
                    Placeholder::make('')->content(''),
                    TextInput::make('base_disk_mb')->numeric()->suffix('MB')->label('Base Disk')->required(),
                    TextInput::make('disk_per_slot_mb')->numeric()->suffix('MB')->label('Disk/Slot')->required(),
                    TextInput::make('disk_mb')->numeric()->suffix('MB')->label('Default Total Disk'),
                ]),

            Section::make('Features')
                ->schema([
                    TagsInput::make('features')
                        ->placeholder('Feature hinzufügen...')
                        ->suggestions(['FastDL', 'DDoS Protection', 'Daily Backups', 'Web Panel', 'Mod Support', 'Priority Support', 'Custom Domain', 'Wolffiles Integration', 'OSP Support']),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('game')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'et' => '🎮 ET',
                        'etl' => '🎮 ET:L',
                        'rtcw' => '🎮 RtCW',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'etl' => 'success',
                        'et' => 'warning',
                        'rtcw' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('min_slots')->label('Min'),
                TextColumn::make('max_slots')->label('Max'),
                TextColumn::make('price_per_slot_monthly')
                    ->label('€/Slot/Mo')
                    ->money('EUR'),
                TextColumn::make('price_example')
                    ->label('24 Slots/Mo')
                    ->getStateUsing(fn (ServerProduct $r) => $r->calculatePrice(24, 'monthly'))
                    ->money('EUR'),
                TextColumn::make('orders_count')
                    ->label('Orders')
                    ->counts('orders'),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('sort_order')->label('#')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServerProducts::route('/'),
            'create' => CreateServerProduct::route('/create'),
            'edit' => EditServerProduct::route('/{record}/edit'),
        ];
    }
}
