<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use App\Filament\Resources\ServerOrderResource\Pages\ListServerOrders;
use App\Filament\Resources\ServerOrderResource\Pages\CreateServerOrder;
use App\Filament\Resources\ServerOrderResource\Pages\EditServerOrder;
use App\Filament\Resources\ServerOrderResource\Pages;
use App\Models\ServerOrder;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ServerOrderResource extends Resource
{
    protected static ?string $model = ServerOrder::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-server-stack';
    protected static string | \UnitEnum | null $navigationGroup = 'Server Hosting';
    protected static ?string $navigationLabel = 'Orders / Servers';
    protected static ?int $navigationSort = 2;


    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Server Details')
                ->columns(2)
                ->schema([
                    Select::make('user_id')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->required(),
                    Select::make('product_id')
                        ->relationship('product', 'name')
                        ->required(),
                    TextInput::make('server_name')->required(),
                    Select::make('game')
                        ->options(['et' => 'ET 2.60b', 'etl' => 'ET: Legacy', 'rtcw' => 'RtCW'])
                        ->required(),
                    Select::make('mod')
                        ->options([
                            'etmain' => 'Vanilla',
                            'etpro' => 'ETPro',
                            'jaymod' => 'jaymod',
                            'nitmod' => 'N!tmod',
                            'noquarter' => 'NoQuarter',
                            'silent' => 'Silent Mod',
                            'legacy' => 'Legacy Mod',
                        ])->default('etmain'),
                    TextInput::make('slots')->numeric()->required(),
                    Select::make('status')
                        ->options([
                            'pending' => '⏳ Pending',
                            'provisioning' => '🔄 Provisioning',
                            'active' => '✅ Active',
                            'suspended' => '⏸️ Suspended',
                            'terminated' => '❌ Terminated',
                            'error' => '🚨 Error',
                        ])->required(),
                    Select::make('billing_period')
                        ->options([
                            'daily' => 'Täglich',
                            'weekly' => 'Wöchentlich',
                            'monthly' => 'Monatlich',
                            'quarterly' => 'Vierteljährlich',
                        ])->default('monthly'),
                ]),

            Section::make('Connection')
                ->columns(3)
                ->schema([
                    TextInput::make('ip_address'),
                    TextInput::make('port')->numeric(),
                    TextInput::make('rcon_password')->password()->revealable(),
                ]),

            Section::make('Billing')
                ->columns(2)
                ->schema([
                    TextInput::make('price_paid')->numeric()->prefix('€'),
                    DateTimePicker::make('paid_until'),
                    Toggle::make('auto_renew'),
                ]),

            Section::make('Pterodactyl')
                ->columns(2)
                ->collapsed()
                ->schema([
                    TextInput::make('pterodactyl_server_id'),
                    TextInput::make('pterodactyl_user_id'),
                    Select::make('node_id')
                        ->relationship('node', 'name'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('user.name')->label('User')->searchable()->sortable(),
                TextColumn::make('server_name')->label('Server')->searchable()->limit(25),
                TextColumn::make('game')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'et' => 'ET', 'etl' => 'ET:L', 'rtcw' => 'RtCW', default => $state,
                    }),
                TextColumn::make('mod')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'etmain' => 'Vanilla', default => $state,
                    }),
                TextColumn::make('slots')->label('Slots'),
                TextColumn::make('connection')
                    ->label('IP:Port')
                    ->getStateUsing(fn (ServerOrder $r) => $r->ip_address ? "{$r->ip_address}:{$r->port}" : '-')
                    ->copyable()
                    ->size('sm'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'active' => 'success',
                        'provisioning' => 'warning',
                        'suspended' => 'danger',
                        'terminated' => 'gray',
                        'error' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('price_paid')->money('EUR')->label('Preis'),
                TextColumn::make('paid_until')
                    ->label('Läuft bis')
                    ->dateTime('d.m.Y')
                    ->color(fn (ServerOrder $r) => $r->paid_until && $r->paid_until < now() ? 'danger' : ($r->paid_until && $r->paid_until < now()->addDays(7) ? 'warning' : null)),
                TextColumn::make('node.name')->label('Node')->size('sm'),
                TextColumn::make('created_at')->dateTime('d.m.Y')->label('Erstellt')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => '✅ Active',
                        'suspended' => '⏸️ Suspended',
                        'pending' => '⏳ Pending',
                        'terminated' => '❌ Terminated',
                        'error' => '🚨 Error',
                    ]),
                SelectFilter::make('game')
                    ->options(['et' => 'ET', 'etl' => 'ET: Legacy', 'rtcw' => 'RtCW']),
                Filter::make('expiring')
                    ->label('Läuft bald ab')
                    ->query(fn ($query) => $query->where('paid_until', '<=', now()->addDays(7))->where('paid_until', '>', now())),
                Filter::make('expired')
                    ->label('Abgelaufen')
                    ->query(fn ($query) => $query->where('paid_until', '<', now())->where('status', '!=', 'terminated')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServerOrders::route('/'),
            'create' => CreateServerOrder::route('/create'),
            'edit' => EditServerOrder::route('/{record}/edit'),
        ];
    }
}
