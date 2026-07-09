<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use App\Models\EttvSlot;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use App\Filament\Resources\EventResource\Pages\ListEvents;
use App\Filament\Resources\EventResource\Pages\CreateEvent;
use App\Filament\Resources\EventResource\Pages\EditEvent;
use App\Filament\Resources\EventResource\Pages;
use App\Models\Event;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';
    protected static string | \UnitEnum | null $navigationGroup = 'ETTV';
    protected static ?string $navigationLabel = 'Events';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Event Details')->schema([
                TextInput::make('title')->required()->maxLength(128)->columnSpanFull(),
                Textarea::make('description')->maxLength(2000)->columnSpanFull(),
                DateTimePicker::make('starts_at')->required(),
                DateTimePicker::make('ends_at'),
            ])->columns(2),
            Section::make('Match Info')->schema([
                TextInput::make('team_axis')->label('Team Axis')->maxLength(64),
                TextInput::make('team_allies')->label('Team Allies')->maxLength(64),
                TextInput::make('map_name')->maxLength(64),
                Select::make('match_type')->options([
                    '6on6' => '6on6', '3on3' => '3on3', '2on2' => '2on2', '1on1' => '1on1', 'public' => 'Public',
                ]),
                Select::make('gametype')->options([
                    'stopwatch' => 'Stopwatch', 'objective' => 'Objective', 'lms' => 'LMS', 'campaign' => 'Campaign',
                ])->default('stopwatch'),
                Select::make('mod_name')->options([
                    'etpro' => 'ETPro', 'etlegacy' => 'ET Legacy', 'jaymod' => 'Jaymod',
                    'noquarter' => 'NoQuarter', 'silent' => 'Silent', 'etmain' => 'Vanilla',
                ])->default('etpro'),
            ])->columns(3),
            Section::make('Server & ETTV')->schema([
                TextInput::make('match_server_ip')->label('Match Server IP'),
                TextInput::make('match_server_port')->label('Port')->numeric(),
                Toggle::make('ettv_enabled')->label('ETTV Relay')->default(true),
                Select::make('ettv_slot')->label('Reserved ETTV Slot')
                    ->options(fn () => EttvSlot::pluck('port', 'slot_number')
                        ->mapWithKeys(fn ($port, $slot) => [$slot => "Slot {$slot} (:{$port})"]))
                    ->nullable(),
            ])->columns(2),
            Section::make('Moderation')->schema([
                Select::make('status')->options([
                    'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected',
                    'live' => 'LIVE', 'completed' => 'Completed', 'cancelled' => 'Cancelled',
                ])->required(),
                TextInput::make('rejection_reason')->maxLength(255),
                Toggle::make('is_featured')->label('Featured'),
            ])->columns(3),
            Section::make('Result')->schema([
                TextInput::make('score_axis')->label('Score Axis')->numeric(),
                TextInput::make('score_allies')->label('Score Allies')->numeric(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'pending' => 'warning', 'approved' => 'info', 'rejected' => 'danger',
                    'live' => 'success', default => 'gray',
                }),
                TextColumn::make('title')->searchable()->limit(40),
                TextColumn::make('starts_at')->label('Datum')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('team_axis')->label('Match')->formatStateUsing(fn ($state, $record) =>
                    ($record->team_axis && $record->team_allies) ? "{$record->team_axis} vs {$record->team_allies}" : '-'),
                TextColumn::make('map_name')->label('Map'),
                TextColumn::make('match_type')->label('Format'),
                IconColumn::make('ettv_enabled')->label('ETTV')->boolean(),
                TextColumn::make('submitter.name')->label('Von'),
                IconColumn::make('is_featured')->label('Featured')->boolean(),
            ])
            ->defaultSort('starts_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending', 'approved' => 'Approved', 'live' => 'Live', 'completed' => 'Completed',
                ]),
            ])
            ->recordActions([
                Action::make('approve')->label('Approve')->icon('heroicon-o-check-circle')->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Event $record) => $record->isPending())
                    ->action(function (Event $record) {
                        $record->approve(auth()->user());
                        Notification::make()->title('Event genehmigt!')->success()->send();
                    }),
                Action::make('reject')->label('Reject')->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn (Event $record) => $record->isPending())
                    ->schema([TextInput::make('reason')->label('Grund')->required()])
                    ->action(function (Event $record, array $data) {
                        $record->reject($data['reason'], auth()->user());
                        Notification::make()->title('Event abgelehnt')->warning()->send();
                    }),
                Action::make('go_live')->label('GO LIVE')->icon('heroicon-o-signal')->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Event $record) => $record->isApproved())
                    ->action(function (Event $record) {
                        $record->goLive();
                        Notification::make()->title('Event ist LIVE!')->success()->send();
                    }),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvents::route('/'),
            'create' => CreateEvent::route('/create'),
            'edit' => EditEvent::route('/{record}/edit'),
        ];
    }
}
