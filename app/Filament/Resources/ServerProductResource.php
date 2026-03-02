<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServerProductResource\Pages;
use App\Models\ServerProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServerProductResource extends Resource
{
    protected static ?string $model = ServerProduct::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Server Hosting';
    protected static ?string $navigationLabel = 'Products';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Produkt Info')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')->required()->maxLength(255),
                    Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true),
                    Forms\Components\Select::make('game')
                        ->options(['et' => 'ET 2.60b', 'etl' => 'ET: Legacy', 'rtcw' => 'RtCW'])
                        ->required(),
                    Forms\Components\Toggle::make('is_active')->default(true),
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                    Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
                ]),

            Forms\Components\Section::make('Slot Konfiguration')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('min_slots')->numeric()->required()->default(2)->minValue(1),
                    Forms\Components\TextInput::make('max_slots')->numeric()->required()->default(64)->maxValue(128),
                    Forms\Components\TextInput::make('slots')->numeric()->label('Default Slots')->default(24),
                ]),

            Forms\Components\Section::make('Preis pro Slot')
                ->description('Preis pro Slot pro Zeitraum. Endpreis = Slots × Preis pro Slot')
                ->columns(4)
                ->schema([
                    Forms\Components\TextInput::make('price_per_slot_daily')->numeric()->prefix('€')->step(0.01)->required(),
                    Forms\Components\TextInput::make('price_per_slot_weekly')->numeric()->prefix('€')->step(0.01)->required(),
                    Forms\Components\TextInput::make('price_per_slot_monthly')->numeric()->prefix('€')->step(0.01)->required(),
                    Forms\Components\TextInput::make('price_per_slot_quarterly')->numeric()->prefix('€')->step(0.01)->required(),
                ]),

            Forms\Components\Section::make('Beispielpreise (feste Pakete, für Anzeige)')
                ->columns(4)
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('price_daily')->numeric()->prefix('€')->step(0.01),
                    Forms\Components\TextInput::make('price_weekly')->numeric()->prefix('€')->step(0.01),
                    Forms\Components\TextInput::make('price_monthly')->numeric()->prefix('€')->step(0.01),
                    Forms\Components\TextInput::make('price_quarterly')->numeric()->prefix('€')->step(0.01),
                ]),

            Forms\Components\Section::make('Ressourcen pro Slot')
                ->description('Base + (Slots × Per Slot) = Total')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('base_memory_mb')->numeric()->suffix('MB')->label('Base RAM')->required(),
                    Forms\Components\TextInput::make('memory_per_slot_mb')->numeric()->suffix('MB')->label('RAM/Slot')->required(),
                    Forms\Components\TextInput::make('memory_mb')->numeric()->suffix('MB')->label('Default Total RAM'),
                    Forms\Components\TextInput::make('cpu_per_slot_percent')->numeric()->suffix('%')->label('CPU/Slot')->required(),
                    Forms\Components\TextInput::make('cpu_percent')->numeric()->suffix('%')->label('Default Total CPU'),
                    Forms\Components\Placeholder::make('')->content(''),
                    Forms\Components\TextInput::make('base_disk_mb')->numeric()->suffix('MB')->label('Base Disk')->required(),
                    Forms\Components\TextInput::make('disk_per_slot_mb')->numeric()->suffix('MB')->label('Disk/Slot')->required(),
                    Forms\Components\TextInput::make('disk_mb')->numeric()->suffix('MB')->label('Default Total Disk'),
                ]),

            Forms\Components\Section::make('Features')
                ->schema([
                    Forms\Components\TagsInput::make('features')
                        ->placeholder('Feature hinzufügen...')
                        ->suggestions(['FastDL', 'DDoS Protection', 'Daily Backups', 'Web Panel', 'Mod Support', 'Priority Support', 'Custom Domain', 'Wolffiles Integration', 'OSP Support']),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('game')
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
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('min_slots')->label('Min'),
                Tables\Columns\TextColumn::make('max_slots')->label('Max'),
                Tables\Columns\TextColumn::make('price_per_slot_monthly')
                    ->label('€/Slot/Mo')
                    ->money('EUR'),
                Tables\Columns\TextColumn::make('price_example')
                    ->label('24 Slots/Mo')
                    ->getStateUsing(fn (ServerProduct $r) => $r->calculatePrice(24, 'monthly'))
                    ->money('EUR'),
                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Orders')
                    ->counts('orders'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->label('#')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServerProducts::route('/'),
            'create' => Pages\CreateServerProduct::route('/create'),
            'edit' => Pages\EditServerProduct::route('/{record}/edit'),
        ];
    }
}
