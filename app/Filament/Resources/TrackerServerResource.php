<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use App\Jobs\Tracker\PollServerJob;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Resources\TrackerServerResource\Pages\ListTrackerServers;
use App\Filament\Resources\TrackerServerResource\Pages\CreateTrackerServer;
use App\Filament\Resources\TrackerServerResource\Pages\EditTrackerServer;
use App\Filament\Resources\TrackerServerResource\Pages;
use App\Models\Tracker\TrackerServer;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TrackerServerResource extends Resource
{
    protected static ?string $model = TrackerServer::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-server-stack';
    protected static string | \UnitEnum | null $navigationGroup = 'Tracker';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Servers';



    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Server')
                ->schema([
                    Select::make('game_id')
                        ->relationship('game', 'short_name')
                        ->required(),
                    TextInput::make('ip')->required()->maxLength(45),
                    TextInput::make('port')->numeric()->required()->default(27960),
                    TextInput::make('hostname')->maxLength(500),
                    TextInput::make('hostname_clean')->maxLength(255),
                    TextInput::make('slug')->maxLength(50)
                        ->regex('/^[a-z][a-z0-9-]+$/')
                        ->rule('not_in:manage,claim,claims,create,edit,delete,admin,new,tracker,servers')
                        ->unique(ignoreRecord: true)
                        ->helperText('Optional public URL slug. Admin bypass: no 30-day lock.'),
                ])->columns(2),

            Section::make('Public Page Content')
                ->description('Visible on the server\'s public page (/servers/{id})')
                ->collapsed()
                ->schema([
                    Textarea::make('description')
                        ->label('Description')
                        ->rows(4)
                        ->maxLength(20000)
                        ->columnSpanFull()
                        ->helperText('Markdown + BBCode supported.'),
                    Textarea::make('rules')
                        ->label('Server Rules')
                        ->rows(6)
                        ->maxLength(20000)
                        ->columnSpanFull()
                        ->helperText('Markdown + BBCode supported.'),
                    TextInput::make('server_logo_url')->label('Logo URL')->url()->maxLength(500),
                    TextInput::make('server_banner_url')->label('Banner URL')->url()->maxLength(500),
                    TextInput::make('server_website')->label('Website')->url()->maxLength(255),
                    TextInput::make('server_discord')->label('Discord')->maxLength(255),
                    TextInput::make('server_email')->label('Email')->email()->maxLength(255),
                ])->columns(2),
            Section::make('Clan Link')
                ->collapsed()
                ->schema([
                    Select::make('claimed_by_clan_id')
                        ->label('Linked clan')
                        ->relationship('clan', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->helperText('Linked clan appears on the clan\'s public page.'),
                    Toggle::make('is_visible_for_clan')
                        ->label('Show on clan page')
                        ->helperText('Toggle visibility independent of link.'),
                ])->columns(2),
            Section::make('Status')
                ->schema([
                    Toggle::make('is_online'),
                    Toggle::make('is_manually_added'),
                    Select::make('status')
                        ->options(['active' => 'Active', 'inactive' => 'Inactive', 'pending' => 'Pending', 'removed' => 'Removed', 'banned' => 'Banned'])
                        ->default('active'),
                ])->columns(3),

            Section::make('Polling')
                ->schema([
                    TextInput::make('custom_poll_interval')
                        ->label('Custom Poll Interval')
                        ->numeric()
                        ->minValue(15)
                        ->maxValue(3600)
                        ->suffix('seconds')
                        ->placeholder('Auto')
                        ->helperText('Overrides online cadence AND offline backoff. 15–3600 s. Leave empty for default.'),
                    Toggle::make('polling_paused')
                        ->label('Pause Polling')
                        ->helperText('Server stays in DB but is not polled.'),
                    Placeholder::make('last_poll_display')
                        ->label('Last Poll')
                        ->content(fn ($record) => $record?->last_poll_at?->diffForHumans() ?? '—'),
                    Placeholder::make('next_poll_display')
                        ->label('Next Poll')
                        ->content(fn ($record) => $record?->next_poll_at?->diffForHumans() ?? '—'),
                    Placeholder::make('failures_display')
                        ->label('Consecutive Failures')
                        ->content(fn ($record) => (string) ($record?->poll_failures ?? 0)),
                ])
                ->columns(3),

            Section::make('Location')
                ->schema([
                    TextInput::make('country')->maxLength(100),
                    TextInput::make('country_code')->maxLength(2),
                    TextInput::make('city')->maxLength(100),
                ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_online')
                    ->boolean()
                    ->label('On')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                TextColumn::make('game.short_name')->label('Game')->sortable(),
                TextColumn::make('hostname_clean')->label('Server')->limit(40)->searchable()->sortable(),
                TextColumn::make('ip')->label('IP')->searchable(),
                TextColumn::make('current_map')->label('Map')->sortable(),
                TextColumn::make('current_players')->label('Players')->sortable()
                    ->formatStateUsing(fn ($record) => $record->current_players . '/' . $record->max_players),
                TextColumn::make('country_code')->label('CC')->sortable(),
                TextColumn::make('mod_name')->label('Mod')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        'removed' => 'danger',
                        'banned' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('current_players', 'desc')
            ->filters([
                SelectFilter::make('game_id')
                    ->relationship('game', 'short_name')
                    ->label('Game'),
                TernaryFilter::make('is_online')->label('Online'),
                SelectFilter::make('status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive', 'pending' => 'Pending', 'removed' => 'Removed', 'banned' => 'Banned']),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('rescan')
                        ->label('Rescan')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Rescan selected servers')
                        ->modalDescription('Sets status to active, resets the failure counter and queues an immediate poll.')
                        ->modalSubmitActionLabel('Rescan')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $max = 200;
                            $total = $records->count();
                            $batch = $records->take($max);

                            foreach ($batch as $record) {
                                $record->update([
                                    'status' => 'active',
                                    'poll_failures' => 0,
                                    'next_poll_at' => now(),
                                ]);

                                PollServerJob::dispatch($record->id)->onQueue('tracker-low');
                            }

                            $n = Notification::make()
                                ->title($batch->count() . ' server(s) queued for rescan');

                            if ($total > $max) {
                                $n->body(($total - $max) . ' skipped - limit is ' . $max . ' per run.')->warning();
                            } else {
                                $n->success();
                            }

                            $n->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrackerServers::route('/'),
            'create' => CreateTrackerServer::route('/create'),
            'edit' => EditTrackerServer::route('/{record}/edit'),
        ];
    }
}
