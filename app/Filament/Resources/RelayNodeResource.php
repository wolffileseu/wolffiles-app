<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\RelayNodeResource\Pages\ListRelayNodes;
use App\Filament\Resources\RelayNodeResource\Pages\CreateRelayNode;
use App\Filament\Resources\RelayNodeResource\Pages\EditRelayNode;
use App\Models\RelayNode;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class RelayNodeResource extends Resource
{
    protected static ?string $model = RelayNode::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-signal';
    protected static string | \UnitEnum | null $navigationGroup = 'Server Hosting';
    protected static ?string $navigationLabel = 'Relay Nodes';
    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Node Info')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(100)
                        ->placeholder('Relay DE-1 Falkenstein'),
                    TextInput::make('hostname')
                        ->maxLength(255)
                        ->placeholder('relay-de1.wolffiles.eu')
                        ->label('Hostname'),
                    Select::make('region')
                        ->options([
                            'eu'    => 'Europe (generic)',
                            'eu-de' => 'Germany',
                            'eu-nl' => 'Netherlands',
                            'eu-pl' => 'Poland',
                            'eu-fr' => 'France',
                            'na'    => 'North America',
                        ])
                        ->default('eu')
                        ->required(),
                    Toggle::make('enabled')
                        ->default(true)
                        ->label('Enabled')
                        ->helperText('Disabled nodes are never handed out to clients.'),
                ]),

            Section::make('Networking')
                ->columns(2)
                ->schema([
                    TextInput::make('ws_url')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('wss://relay-de1.wolffiles.eu')
                        ->label('WebSocket URL')
                        ->helperText('Public endpoint the browser client connects to.'),
                    TextInput::make('ipv6_prefix')
                        ->maxLength(64)
                        ->placeholder('2a01:db8:1234:5678::/64')
                        ->label('IPv6 Prefix')
                        ->helperText('Routed prefix used for per-session source addresses.'),
                    TextInput::make('ipv4_pool')
                        ->maxLength(255)
                        ->placeholder('203.0.113.10, 203.0.113.11')
                        ->label('IPv4 Pool')
                        ->helperText('Comma separated. Needed for IPv4-only game servers.')
                        ->columnSpanFull(),
                    TextInput::make('agent_secret')
                        ->required()
                        ->maxLength(128)
                        ->password()
                        ->revealable()
                        ->default(fn () => Str::random(64))
                        ->label('Agent Secret')
                        ->helperText('Shared secret for HMAC ticket validation and heartbeat auth.')
                        ->columnSpanFull(),
                ]),

            Section::make('Capacity')
                ->columns(2)
                ->schema([
                    TextInput::make('max_sessions')
                        ->numeric()
                        ->default(200)
                        ->minValue(1)
                        ->maxValue(65535)
                        ->required()
                        ->label('Max Sessions'),
                    TextInput::make('active_sessions')
                        ->numeric()
                        ->default(0)
                        ->disabled()
                        ->dehydrated(false)
                        ->label('Active Sessions')
                        ->helperText('Reported by the agent.'),
                ]),

            Section::make('Notes')
                ->collapsed()
                ->schema([
                    Textarea::make('notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('region')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'online'   => 'success',
                        'degraded' => 'warning',
                        'offline'  => 'danger',
                        default    => 'gray',
                    })
                    ->sortable(),
                IconColumn::make('enabled')
                    ->boolean()
                    ->label('On'),
                TextColumn::make('active_sessions')
                    ->label('Sessions')
                    ->formatStateUsing(fn ($state, RelayNode $record): string =>
                        $state . ' / ' . $record->max_sessions)
                    ->sortable(),
                TextColumn::make('load_avg')
                    ->label('Load')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('agent_version')
                    ->label('Agent')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('last_heartbeat_at')
                    ->label('Heartbeat')
                    ->since()
                    ->placeholder('never')
                    ->color(fn (?RelayNode $record): string =>
                        $record && $record->hasFreshHeartbeat() ? 'success' : 'danger')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'online'   => 'Online',
                        'offline'  => 'Offline',
                        'degraded' => 'Degraded',
                        'disabled' => 'Disabled',
                    ]),
                SelectFilter::make('region')
                    ->options([
                        'eu'    => 'Europe (generic)',
                        'eu-de' => 'Germany',
                        'eu-nl' => 'Netherlands',
                        'eu-pl' => 'Poland',
                        'eu-fr' => 'France',
                        'na'    => 'North America',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('name')
            ->poll('30s');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListRelayNodes::route('/'),
            'create' => CreateRelayNode::route('/create'),
            'edit'   => EditRelayNode::route('/{record}/edit'),
        ];
    }
}
