<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Models\Event;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'ETTV';
    protected static ?string $navigationLabel = 'Events';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Event Details')->schema([
                Forms\Components\TextInput::make('title')->required()->maxLength(128)->columnSpanFull(),
                Forms\Components\Textarea::make('description')->maxLength(2000)->columnSpanFull(),
                Forms\Components\DateTimePicker::make('starts_at')->required(),
                Forms\Components\DateTimePicker::make('ends_at'),
            ])->columns(2),
            Forms\Components\Section::make('Match Info')->schema([
                Forms\Components\TextInput::make('team_axis')->label('Team Axis')->maxLength(64),
                Forms\Components\TextInput::make('team_allies')->label('Team Allies')->maxLength(64),
                Forms\Components\TextInput::make('map_name')->maxLength(64),
                Forms\Components\Select::make('match_type')->options([
                    '6on6' => '6on6', '3on3' => '3on3', '2on2' => '2on2', '1on1' => '1on1', 'public' => 'Public',
                ]),
                Forms\Components\Select::make('gametype')->options([
                    'stopwatch' => 'Stopwatch', 'objective' => 'Objective', 'lms' => 'LMS', 'campaign' => 'Campaign',
                ])->default('stopwatch'),
                Forms\Components\Select::make('mod_name')->options([
                    'etpro' => 'ETPro', 'etlegacy' => 'ET Legacy', 'jaymod' => 'Jaymod',
                    'noquarter' => 'NoQuarter', 'silent' => 'Silent', 'etmain' => 'Vanilla',
                ])->default('etpro'),
            ])->columns(3),
            Forms\Components\Section::make('Server & ETTV')->schema([
                Forms\Components\TextInput::make('match_server_ip')->label('Match Server IP'),
                Forms\Components\TextInput::make('match_server_port')->label('Port')->numeric(),
                Forms\Components\Toggle::make('ettv_enabled')->label('ETTV Relay')->default(true),
                Forms\Components\Select::make('ettv_slot')->label('Reserved ETTV Slot')
                    ->options(fn () => \App\Models\EttvSlot::pluck('port', 'slot_number')
                        ->mapWithKeys(fn ($port, $slot) => [$slot => "Slot {$slot} (:{$port})"]))
                    ->nullable(),
            ])->columns(2),
            Forms\Components\Section::make('Moderation')->schema([
                Forms\Components\Select::make('status')->options([
                    'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected',
                    'live' => 'LIVE', 'completed' => 'Completed', 'cancelled' => 'Cancelled',
                ])->required(),
                Forms\Components\TextInput::make('rejection_reason')->maxLength(255),
                Forms\Components\Toggle::make('is_featured')->label('Featured'),
            ])->columns(3),
            Forms\Components\Section::make('Result')->schema([
                Forms\Components\TextInput::make('score_axis')->label('Score Axis')->numeric(),
                Forms\Components\TextInput::make('score_allies')->label('Score Allies')->numeric(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'pending' => 'warning', 'approved' => 'info', 'rejected' => 'danger',
                    'live' => 'success', default => 'gray',
                }),
                Tables\Columns\TextColumn::make('title')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('starts_at')->label('Datum')->dateTime('d.m.Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('team_axis')->label('Match')->formatStateUsing(fn ($state, $record) =>
                    ($record->team_axis && $record->team_allies) ? "{$record->team_axis} vs {$record->team_allies}" : '-'),
                Tables\Columns\TextColumn::make('map_name')->label('Map'),
                Tables\Columns\TextColumn::make('match_type')->label('Format'),
                Tables\Columns\IconColumn::make('ettv_enabled')->label('ETTV')->boolean(),
                Tables\Columns\TextColumn::make('submitter.name')->label('Von'),
                Tables\Columns\IconColumn::make('is_featured')->label('Featured')->boolean(),
            ])
            ->defaultSort('starts_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'Pending', 'approved' => 'Approved', 'live' => 'Live', 'completed' => 'Completed',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')->label('Approve')->icon('heroicon-o-check-circle')->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Event $record) => $record->isPending())
                    ->action(function (Event $record) {
                        $record->approve(auth()->user());
                        Notification::make()->title('Event genehmigt!')->success()->send();
                    }),
                Tables\Actions\Action::make('reject')->label('Reject')->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn (Event $record) => $record->isPending())
                    ->form([Forms\Components\TextInput::make('reason')->label('Grund')->required()])
                    ->action(function (Event $record, array $data) {
                        $record->reject($data['reason'], auth()->user());
                        Notification::make()->title('Event abgelehnt')->warning()->send();
                    }),
                Tables\Actions\Action::make('go_live')->label('GO LIVE')->icon('heroicon-o-signal')->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Event $record) => $record->isApproved())
                    ->action(function (Event $record) {
                        $record->goLive();
                        Notification::make()->title('Event ist LIVE!')->success()->send();
                    }),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
