<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EttvSlotResource\Pages;
use App\Models\EttvSlot;
use App\Services\PterodactylService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class EttvSlotResource extends Resource
{
    protected static ?string $model = EttvSlot::class;
    protected static ?string $navigationIcon = 'heroicon-o-tv';
    protected static ?string $navigationGroup = 'ETTV';
    protected static ?string $navigationLabel = 'Server Slots';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Slot Info')->schema([
                Forms\Components\TextInput::make('slot_number')->required()->numeric(),
                Forms\Components\TextInput::make('port')->required()->numeric(),
                Forms\Components\TextInput::make('pterodactyl_uuid')->required()->maxLength(36),
            ])->columns(3),
            Forms\Components\Section::make('Status')->schema([
                Forms\Components\Select::make('status')->options([
                    'idle' => 'Idle', 'starting' => 'Starting', 'playing' => 'Playing',
                    'relay' => 'Live Relay', 'reserved' => 'Reserved', 'error' => 'Error',
                ]),
                Forms\Components\Select::make('mode')->options([
                    'demo' => 'Demo', 'relay' => 'Relay', 'showcase' => 'Showcase',
                ])->nullable(),
                Forms\Components\TextInput::make('reservation_reason')->maxLength(255),
            ])->columns(3),
            Forms\Components\Section::make('Match / Demo')->schema([
                Forms\Components\TextInput::make('demo_name'),
                Forms\Components\TextInput::make('map_name'),
                Forms\Components\TextInput::make('match_server_ip')->label('Relay IP'),
                Forms\Components\TextInput::make('match_server_port')->label('Relay Port')->numeric(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('slot_number')->label('Slot')->sortable()->badge(),
                Tables\Columns\TextColumn::make('port')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->formatStateUsing(fn ($state, $record) => $record->getStatusBadge() . ' ' . ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'idle' => 'gray', 'starting' => 'warning', 'playing' => 'info',
                        'relay' => 'success', 'reserved' => 'warning', 'error' => 'danger', default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('mode')->badge()->color(fn (?string $state): string => match ($state) {
                    'demo' => 'info', 'relay' => 'success', 'showcase' => 'warning', default => 'gray',
                }),
                Tables\Columns\TextColumn::make('demo_name')->label('Content')->formatStateUsing(function ($state, $record) {
                    if ($record->mode === 'relay' && $record->match_server_ip) {
                        return $record->match_server_ip . ':' . $record->match_server_port;
                    }
                    return $state ?: '-';
                }),
                Tables\Columns\TextColumn::make('map_name')->label('Map'),
                Tables\Columns\TextColumn::make('spectator_count')->label('Specs')->alignCenter(),
                Tables\Columns\TextColumn::make('started_at')->label('Runtime')->since()->placeholder('-'),
                Tables\Columns\TextColumn::make('user.name')->label('Started By')->placeholder('-'),
            ])
            ->defaultSort('slot_number')
            ->actions([
                // ── START RELAY ──
                Tables\Actions\Action::make('start_relay')
                    ->label('Start Relay')
                    ->icon('heroicon-o-signal')
                    ->color('success')
                    ->visible(fn (EttvSlot $record) => $record->isAvailable() || $record->status === 'reserved')
                    ->form([
                        Forms\Components\TextInput::make('server_ip')
                            ->label('Match Server IP')
                            ->required()
                            ->placeholder('192.168.1.100'),
                        Forms\Components\TextInput::make('server_port')
                            ->label('Match Server Port')
                            ->required()
                            ->numeric()
                            ->default(27960),
                        Forms\Components\TextInput::make('relay_password')
                            ->label('ETTV/Slave Password')
                            ->placeholder('optional'),
                    ])
                    ->action(function (EttvSlot $record, array $data) {
                        $ptero = app(PterodactylService::class);

                        $record->update([
                            'status' => 'starting',
                            'mode' => 'relay',
                            'match_server_ip' => $data['server_ip'],
                            'match_server_port' => (int) $data['server_port'],
                            'user_id' => auth()->id(),
                            'started_at' => now(),
                            'expires_at' => now()->addHours(3),
                        ]);

                        $success = $ptero->startRelay(
                            $record,
                            $data['server_ip'],
                            (int) $data['server_port'],
                            $data['relay_password'] ?? ''
                        );

                        if ($success) {
                            $record->update(['status' => 'relay']);
                            Notification::make()
                                ->title("Relay gestartet!")
                                ->body("Slot {$record->slot_number} verbindet sich mit {$data['server_ip']}:{$data['server_port']}")
                                ->success()->send();
                        } else {
                            $record->update(['status' => 'error']);
                            Notification::make()
                                ->title('Relay Start fehlgeschlagen!')
                                ->danger()->send();
                        }
                    }),

                // ── START DEMO ──
                Tables\Actions\Action::make('start_demo')
                    ->label('Start Demo')
                    ->icon('heroicon-o-play')
                    ->color('info')
                    ->visible(fn (EttvSlot $record) => $record->isAvailable() || $record->status === 'reserved')
                    ->form([
                        Forms\Components\TextInput::make('demo_name')
                            ->label('Demo Filename (ohne .tv_84)')
                            ->required()
                            ->default('demo0000')
                            ->placeholder('demo0000'),
                    ])
                    ->action(function (EttvSlot $record, array $data) {
                        $ptero = app(PterodactylService::class);

                        $record->update([
                            'status' => 'starting',
                            'mode' => 'demo',
                            'demo_name' => $data['demo_name'],
                            'user_id' => auth()->id(),
                            'started_at' => now(),
                            'expires_at' => now()->addMinutes(30),
                        ]);

                        $success = $ptero->startDemo($record, $data['demo_name']);

                        if ($success) {
                            $record->update(['status' => 'playing']);
                            Notification::make()
                                ->title("Demo gestartet!")
                                ->body("Slot {$record->slot_number} spielt {$data['demo_name']}")
                                ->success()->send();
                        } else {
                            $record->update(['status' => 'error']);
                            Notification::make()
                                ->title('Demo Start fehlgeschlagen!')
                                ->danger()->send();
                        }
                    }),

                // ── STOP ──
                Tables\Actions\Action::make('stop')
                    ->label('Stop')
                    ->icon('heroicon-o-stop')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (EttvSlot $record) => $record->isRunning())
                    ->action(function (EttvSlot $record) {
                        app(PterodactylService::class)->killServer($record);
                        $record->release();
                        Notification::make()->title('Server gestoppt')->success()->send();
                    }),

                // ── RELEASE ──
                Tables\Actions\Action::make('release')
                    ->label('Release')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (EttvSlot $record) => $record->status === 'reserved')
                    ->action(function (EttvSlot $record) {
                        $record->release();
                        Notification::make()->title('Slot freigegeben')->success()->send();
                    }),

                Tables\Actions\EditAction::make(),
            ])
            ->poll('15s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEttvSlots::route('/'),
            'edit' => Pages\EditEttvSlot::route('/{record}/edit'),
        ];
    }
}
