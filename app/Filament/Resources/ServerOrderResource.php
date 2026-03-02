<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServerOrderResource\Pages;
use App\Models\ServerOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ServerOrderResource extends Resource
{
    protected static ?string $model = ServerOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-server-stack';
    protected static ?string $navigationGroup = 'Server Hosting';
    protected static ?string $navigationLabel = 'Orders / Servers';
    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Server Details')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->required(),
                    Forms\Components\Select::make('product_id')
                        ->relationship('product', 'name')
                        ->required(),
                    Forms\Components\TextInput::make('server_name')->required(),
                    Forms\Components\Select::make('game')
                        ->options(['et' => 'ET 2.60b', 'etl' => 'ET: Legacy', 'rtcw' => 'RtCW'])
                        ->required(),
                    Forms\Components\Select::make('mod')
                        ->options([
                            'etmain' => 'Vanilla',
                            'etpro' => 'ETPro',
                            'jaymod' => 'jaymod',
                            'nitmod' => 'N!tmod',
                            'noquarter' => 'NoQuarter',
                            'silent' => 'Silent Mod',
                            'legacy' => 'Legacy Mod',
                        ])->default('etmain'),
                    Forms\Components\TextInput::make('slots')->numeric()->required(),
                    Forms\Components\Select::make('status')
                        ->options([
                            'pending' => '⏳ Pending',
                            'provisioning' => '🔄 Provisioning',
                            'active' => '✅ Active',
                            'suspended' => '⏸️ Suspended',
                            'terminated' => '❌ Terminated',
                            'error' => '🚨 Error',
                        ])->required(),
                    Forms\Components\Select::make('billing_period')
                        ->options([
                            'daily' => 'Täglich',
                            'weekly' => 'Wöchentlich',
                            'monthly' => 'Monatlich',
                            'quarterly' => 'Vierteljährlich',
                        ])->default('monthly'),
                ]),

            Forms\Components\Section::make('Connection')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('ip_address'),
                    Forms\Components\TextInput::make('port')->numeric(),
                    Forms\Components\TextInput::make('rcon_password')->password()->revealable(),
                ]),

            Forms\Components\Section::make('Billing')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('price_paid')->numeric()->prefix('€'),
                    Forms\Components\DateTimePicker::make('paid_until'),
                    Forms\Components\Toggle::make('auto_renew'),
                ]),

            Forms\Components\Section::make('Pterodactyl')
                ->columns(2)
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('pterodactyl_server_id'),
                    Forms\Components\TextInput::make('pterodactyl_user_id'),
                    Forms\Components\Select::make('node_id')
                        ->relationship('node', 'name'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('User')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('server_name')->label('Server')->searchable()->limit(25),
                Tables\Columns\TextColumn::make('game')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'et' => 'ET', 'etl' => 'ET:L', 'rtcw' => 'RtCW', default => $state,
                    }),
                Tables\Columns\TextColumn::make('mod')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'etmain' => 'Vanilla', default => $state,
                    }),
                Tables\Columns\TextColumn::make('slots')->label('Slots'),
                Tables\Columns\TextColumn::make('connection')
                    ->label('IP:Port')
                    ->getStateUsing(fn (ServerOrder $r) => $r->ip_address ? "{$r->ip_address}:{$r->port}" : '-')
                    ->copyable()
                    ->size('sm'),
                Tables\Columns\TextColumn::make('status')
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
                Tables\Columns\TextColumn::make('price_paid')->money('EUR')->label('Preis'),
                Tables\Columns\TextColumn::make('paid_until')
                    ->label('Läuft bis')
                    ->dateTime('d.m.Y')
                    ->color(fn (ServerOrder $r) => $r->paid_until && $r->paid_until < now() ? 'danger' : ($r->paid_until && $r->paid_until < now()->addDays(7) ? 'warning' : null)),
                Tables\Columns\TextColumn::make('node.name')->label('Node')->size('sm'),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d.m.Y')->label('Erstellt')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => '✅ Active',
                        'suspended' => '⏸️ Suspended',
                        'pending' => '⏳ Pending',
                        'terminated' => '❌ Terminated',
                        'error' => '🚨 Error',
                    ]),
                Tables\Filters\SelectFilter::make('game')
                    ->options(['et' => 'ET', 'etl' => 'ET: Legacy', 'rtcw' => 'RtCW']),
                Tables\Filters\Filter::make('expiring')
                    ->label('Läuft bald ab')
                    ->query(fn ($query) => $query->where('paid_until', '<=', now()->addDays(7))->where('paid_until', '>', now())),
                Tables\Filters\Filter::make('expired')
                    ->label('Abgelaufen')
                    ->query(fn ($query) => $query->where('paid_until', '<', now())->where('status', '!=', 'terminated')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServerOrders::route('/'),
            'create' => Pages\CreateServerOrder::route('/create'),
            'edit' => Pages\EditServerOrder::route('/{record}/edit'),
        ];
    }
}
