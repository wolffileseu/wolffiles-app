<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TestserverResource\Pages;
use App\Jobs\ExpireTestSessionJob;
use App\Models\Testserver;
use App\Services\TestserverService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TestserverResource extends Resource
{
    protected static ?string $model = Testserver::class;
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationGroup = 'Server Hosting';
    protected static ?string $navigationLabel = 'Testservers';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identifikation')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(64),
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(64)
                        ->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('slot_number')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->unique(ignoreRecord: true),
                ]),

            Forms\Components\Section::make('Pterodactyl Anbindung')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('pterodactyl_uuid')
                        ->label('Pterodactyl UUID')
                        ->required()
                        ->maxLength(36)
                        ->helperText('UUID aus Pterodactyl Panel'),
                    Forms\Components\TextInput::make('pterodactyl_server_id')
                        ->label('Pterodactyl Server ID')
                        ->numeric()
                        ->helperText('Die kurze Server-ID (z.B. 23)'),
                    Forms\Components\Select::make('pterodactyl_egg_id')
                        ->label('Egg')
                        ->options([
                            17 => 'ET: Legacy (Wolffiles)',
                            18 => 'ET 2.60b Classic',
                            19 => 'RtCW Multiplayer',
                        ])
                        ->default(17)
                        ->required(),
                ]),

            Forms\Components\Section::make('Connect-Info (für User sichtbar)')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('connect_ip')
                        ->required()
                        ->maxLength(64)
                        ->placeholder('144.76.234.44'),
                    Forms\Components\TextInput::make('connect_port')
                        ->numeric()
                        ->required()
                        ->minValue(1024)
                        ->maxValue(65535),
                ]),

            Forms\Components\Section::make('Defaults (Idle-State)')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('default_mod')
                        ->required()
                        ->default('legacy')
                        ->maxLength(32),
                    Forms\Components\TextInput::make('default_map')
                        ->required()
                        ->default('oasis')
                        ->maxLength(64),
                    Forms\Components\TextInput::make('default_config')
                        ->required()
                        ->default('etl_server.cfg')
                        ->maxLength(64),
                ]),

            Forms\Components\Section::make('Erlaubte Mods')
                ->description('Welche Mods darf der User auf diesem Server starten? Leer lassen = alle aktivierten Mods.')
                ->schema([
                    Forms\Components\CheckboxList::make('allowed_mod_slugs')
                        ->label('Mods')
                        ->options(fn () => \App\Models\TestserverMod::enabled()
                            ->orderBy('sort_order')
                            ->pluck('display_name', 'slug')
                            ->toArray())
                        ->columns(3)
                        ->helperText('Hinweis: Nur Mods die auch zum gewählten Egg passen werden im Public-Dropdown angezeigt.'),
                ])
                ->collapsible()
                ->collapsed(),

            Forms\Components\Section::make('Limits & Sichtbarkeit')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('max_session_minutes')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(120)
                        ->default(20)
                        ->suffix('Minuten'),
                    Forms\Components\TextInput::make('max_players')
                        ->numeric()
                        ->required()
                        ->minValue(2)
                        ->maxValue(64)
                        ->default(16),
                    Forms\Components\Toggle::make('enabled')
                        ->label('Aktiv (im Pool nutzbar)')
                        ->default(true),
                    Forms\Components\Toggle::make('public_visible')
                        ->label('Public sichtbar')
                        ->default(true)
                        ->helperText('Wenn aus: nur Admins sehen ihn'),
                ]),

            Forms\Components\Section::make('Live-Status (read-only)')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('status')
                        ->options([
                            'idle'        => '🟢 Idle',
                            'reserving'   => '🟡 Reserving',
                            'active'      => '🔴 Active',
                            'cleanup'     => '🟠 Cleanup',
                            'offline'     => '⚫ Offline',
                            'maintenance' => '🔧 Maintenance',
                        ])
                        ->required(),
                    Forms\Components\DateTimePicker::make('last_session_at')
                        ->disabled(),
                    Forms\Components\Textarea::make('last_error')
                        ->columnSpanFull()
                        ->disabled(),
                ])
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('slot_number')
                    ->label('#')
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'idle',
                        'warning' => ['reserving', 'cleanup'],
                        'danger'  => 'active',
                        'gray'    => ['offline', 'maintenance'],
                    ])
                    ->formatStateUsing(fn (string $state, Testserver $record) => $record->status_badge . ' ' . ucfirst($state)),
                Tables\Columns\TextColumn::make('connect_string')
                    ->label('Connect')
                    ->copyable()
                    ->copyMessage('Connect-String kopiert')
                    ->fontFamily('mono')
                    ->size('sm'),
                Tables\Columns\TextColumn::make('default_mod')
                    ->label('Mod')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('total_sessions')
                    ->label('Sessions')
                    ->sortable()
                    ->numeric()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('last_session_at')
                    ->label('Letzte Session')
                    ->since()
                    ->placeholder('nie'),
                Tables\Columns\IconColumn::make('enabled')
                    ->boolean()
                    ->label('Aktiv'),
                Tables\Columns\IconColumn::make('public_visible')
                    ->boolean()
                    ->label('Public'),
            ])
            ->defaultSort('slot_number')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'idle' => 'Idle',
                        'active' => 'Active',
                        'offline' => 'Offline',
                        'maintenance' => 'Maintenance',
                    ]),
                Tables\Filters\TernaryFilter::make('enabled'),
            ])
            ->actions([
                // CONNECTION TEST
                Tables\Actions\Action::make('testConnection')
                    ->label('Test')
                    ->icon('heroicon-o-signal')
                    ->color('info')
                    ->action(function (Testserver $record) {
                        $svc = new TestserverService();
                        $res = $svc->getResources($record->pterodactyl_uuid);

                        if (!$res) {
                            Notification::make()
                                ->title('Pterodactyl Connection Failed')
                                ->body('Kein Resources-Response. UUID falsch oder API-Key Problem?')
                                ->danger()
                                ->send();
                            return;
                        }

                        $state = $res['current_state'] ?? '?';
                        $uptime = round(($res['resources']['uptime'] ?? 0) / 1000);
                        $mem = round(($res['resources']['memory_bytes'] ?? 0) / 1024 / 1024);

                        Notification::make()
                            ->title("✅ {$record->name}: {$state}")
                            ->body("Uptime: {$uptime}s · Memory: {$mem}MB")
                            ->success()
                            ->send();
                    }),

                // FORCE STOP
                Tables\Actions\Action::make('forceStop')
                    ->label('Force Stop')
                    ->icon('heroicon-o-stop-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Aktive Session beenden?')
                    ->modalDescription('Beendet die laufende Session sofort und setzt den Server auf Idle.')
                    ->visible(fn (Testserver $record) => in_array($record->status, ['reserving', 'active', 'cleanup']))
                    ->action(function (Testserver $record) {
                        $active = $record->sessions()
                            ->whereIn('status', ['pending','launching','active'])
                            ->latest()
                            ->first();

                        if ($active) {
                            ExpireTestSessionJob::dispatch($active->id, 'forced');
                            Notification::make()
                                ->title('Force-Stop dispatched')
                                ->body("Session #{$active->id} wird beendet (mode: forced)")
                                ->success()
                                ->send();
                        } else {
                            // Kein active session - Server direkt auf idle setzen
                            $record->update(['status' => 'idle']);
                            Notification::make()
                                ->title('Server auf Idle gesetzt')
                                ->success()
                                ->send();
                        }
                    }),

                // RESET STATUS (Notfall: stuck in 'reserving' o.ä.)
                Tables\Actions\Action::make('resetStatus')
                    ->label('Reset')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Server-Status zurücksetzen?')
                    ->modalDescription('Setzt den Server hart auf "idle". Nutze das nur wenn ein Status hängt.')
                    ->action(function (Testserver $record) {
                        $record->update(['status' => 'idle', 'last_error' => 'Manuell reset durch Admin']);
                        Notification::make()->title('Status reset auf idle')->success()->send();
                    }),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->poll('15s'); // Auto-Refresh alle 15s damit Status live aktualisiert
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTestservers::route('/'),
            'create' => Pages\CreateTestserver::route('/create'),
            'edit'   => Pages\EditTestserver::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $active = static::getModel()::where('status', 'active')->count();
        $total  = static::getModel()::where('enabled', true)->count();
        return $active > 0 ? "{$active}/{$total}" : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger'; // rot wenn was läuft
    }
}
