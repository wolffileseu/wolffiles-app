<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TestserverSessionResource\Pages;
use App\Jobs\ExpireTestSessionJob;
use App\Models\TestserverSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TestserverSessionResource extends Resource
{
    protected static ?string $model = TestserverSession::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Server Hosting';
    protected static ?string $navigationLabel = 'Test Sessions';
    protected static ?int $navigationSort = 11;

    /** Sessions können nicht manuell erstellt werden */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        // Edit-Form (alles read-only, nur zum Anschauen)
        return $form->schema([
            Forms\Components\Section::make('Session Info')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('id')->disabled(),
                    Forms\Components\TextInput::make('session_token')->disabled()->columnSpan(2),
                    Forms\Components\Select::make('testserver_id')
                        ->relationship('testserver', 'name')
                        ->disabled(),
                    Forms\Components\TextInput::make('status')->disabled(),
                    Forms\Components\TextInput::make('mod_name')->disabled(),
                ]),

            Forms\Components\Section::make('Map & Connect')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('map_slug')->disabled(),
                    Forms\Components\TextInput::make('map_pk3_filename')->disabled(),
                    Forms\Components\TextInput::make('connect_address')->disabled(),
                    Forms\Components\TextInput::make('connect_password')->disabled(),
                ]),

            Forms\Components\Section::make('User & Tracking')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('ip_address')->disabled(),
                    Forms\Components\TextInput::make('country_code')->disabled(),
                    Forms\Components\Select::make('user_id')
                        ->relationship('user', 'name')
                        ->disabled()
                        ->placeholder('Anonym'),
                    Forms\Components\Textarea::make('user_agent')
                        ->disabled()
                        ->columnSpanFull()
                        ->rows(2),
                ]),

            Forms\Components\Section::make('Timing')
                ->columns(2)
                ->schema([
                    Forms\Components\DateTimePicker::make('reserved_at')->disabled(),
                    Forms\Components\DateTimePicker::make('started_at')->disabled(),
                    Forms\Components\DateTimePicker::make('expires_at')->disabled(),
                    Forms\Components\DateTimePicker::make('ended_at')->disabled(),
                ]),

            Forms\Components\Section::make('Stats')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('peak_players')->disabled()->numeric(),
                    Forms\Components\TextInput::make('total_player_minutes')->disabled()->numeric(),
                    Forms\Components\TextInput::make('snapshot_count')->disabled()->numeric(),
                    Forms\Components\Textarea::make('error_message')
                        ->disabled()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('testserver.name')
                    ->label('Server')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray'    => ['pending', 'launching'],
                        'success' => 'active',
                        'warning' => 'expiring',
                        'info'    => 'expired',
                        'danger'  => ['failed', 'cancelled'],
                    ])
                    ->icon(fn (string $state) => match ($state) {
                        'pending'   => 'heroicon-o-clock',
                        'launching' => 'heroicon-o-arrow-path',
                        'active'    => 'heroicon-o-play-circle',
                        'expiring'  => 'heroicon-o-pause-circle',
                        'expired'   => 'heroicon-o-check-circle',
                        'cancelled' => 'heroicon-o-x-circle',
                        'failed'    => 'heroicon-o-exclamation-triangle',
                        default     => null,
                    }),

                Tables\Columns\TextColumn::make('map_slug')
                    ->label('Map')
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('mod_name')
                    ->label('Mod')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->searchable()
                    ->fontFamily('mono')
                    ->size('sm')
                    ->copyable(),

                Tables\Columns\TextColumn::make('country_code')
                    ->label('Land')
                    ->placeholder('?')
                    ->badge(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->placeholder('Anonym')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('started_at')
                    ->label('Gestartet')
                    ->dateTime('H:i')
                    ->sortable()
                    ->since()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Verbleibt')
                    ->state(function (TestserverSession $record): string {
                        if (!in_array($record->status, ['pending','launching','active'])) {
                            return '—';
                        }
                        $sec = $record->remaining_seconds;
                        if ($sec <= 0) return 'abgelaufen';
                        $min = (int) floor($sec / 60);
                        return "{$min}m " . ($sec % 60) . "s";
                    })
                    ->badge()
                    ->color(fn (TestserverSession $record) =>
                        $record->isActive() ? 'success' : 'gray'
                    ),

                Tables\Columns\TextColumn::make('peak_players')
                    ->label('Peak')
                    ->numeric()
                    ->alignEnd()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m. H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->multiple()
                    ->options([
                        'pending'   => '⏳ Pending',
                        'launching' => '🔄 Launching',
                        'active'    => '✅ Active',
                        'expiring'  => '⏸ Expiring',
                        'expired'   => '✓ Expired',
                        'cancelled' => '❌ Cancelled',
                        'failed'    => '⚠ Failed',
                    ]),

                Tables\Filters\Filter::make('aktive_sessions')
                    ->label('Nur aktive Sessions')
                    ->query(fn (Builder $q) => $q->whereIn('status', ['pending','launching','active'])),

                Tables\Filters\Filter::make('heute')
                    ->label('Heute')
                    ->query(fn (Builder $q) => $q->whereDate('created_at', today())),

                Tables\Filters\Filter::make('diese_woche')
                    ->label('Diese Woche')
                    ->query(fn (Builder $q) => $q->where('created_at', '>=', now()->startOfWeek())),

                Tables\Filters\SelectFilter::make('testserver')
                    ->relationship('testserver', 'name'),

                Tables\Filters\SelectFilter::make('mod_name')
                    ->options(fn () => TestserverSession::query()
                        ->distinct()
                        ->pluck('mod_name', 'mod_name')
                        ->toArray()
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('forceStop')
                    ->label('Stop')
                    ->icon('heroicon-o-stop-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Session beenden?')
                    ->visible(fn (TestserverSession $r) =>
                        in_array($r->status, ['pending','launching','active']))
                    ->action(function (TestserverSession $record) {
                        ExpireTestSessionJob::dispatch($record->id, 'forced');
                        Notification::make()
                            ->title('Session wird beendet')
                            ->body("Session #{$record->id} wird via Worker gestoppt")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('forceStopBulk')
                        ->label('Alle ausgewählten stoppen')
                        ->icon('heroicon-o-stop-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if (in_array($record->status, ['pending','launching','active'])) {
                                    ExpireTestSessionJob::dispatch($record->id, 'forced');
                                    $count++;
                                }
                            }
                            Notification::make()
                                ->title("{$count} Sessions werden gestoppt")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->poll('10s'); // Live-Refresh
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTestserverSessions::route('/'),
            'view'  => Pages\ViewTestserverSession::route('/{record}'),
            'edit'  => Pages\EditTestserverSession::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $active = static::getModel()::active()->count();
        return $active > 0 ? (string) $active : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
