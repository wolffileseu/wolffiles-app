<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlayerReportResource\Pages;
use App\Models\Tracker\TrackerPlayerReport;
use App\Models\Tracker\TrackerBan;
use App\Models\Tracker\TrackerBanEvidence;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PlayerReportResource extends Resource
{
    protected static ?string $model = TrackerPlayerReport::class;
    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationGroup = 'Tracker';
    protected static ?int $navigationSort = 7;
    protected static ?string $navigationLabel = 'Player Reports';
    protected static ?string $modelLabel = 'Player Report';


    public static function getNavigationBadge(): ?string
    {
        $c = TrackerPlayerReport::where('status', 'pending')->count();
        return $c > 0 ? (string) $c : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Report')->schema([
                Infolists\Components\TextEntry::make('reporter.name')->label('Reported by'),
                Infolists\Components\TextEntry::make('player.name_clean')->label('Reported player')
                    ->url(fn ($record) => $record->player ? route('tracker.player.show', $record->player) : null, true),
                Infolists\Components\TextEntry::make('reported_guid')->label('GUID (provided)')->placeholder('—')->copyable(),
                Infolists\Components\TextEntry::make('contact')->label('Contact')->placeholder('—'),
                Infolists\Components\TextEntry::make('description')->label('Description')->columnSpanFull(),
                Infolists\Components\TextEntry::make('status')->badge(),
            ])->columns(2),

            Infolists\Components\Section::make('Evidence (reporter screenshots)')->schema([
                Infolists\Components\ViewEntry::make('evidence')
                    ->view('filament.infolists.report-evidence'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('reporter.name')->label('By')->searchable(),
                Tables\Columns\TextColumn::make('player.name_clean')->label('Player')->searchable()->limit(20)
                    ->url(fn ($record) => $record->player ? route('tracker.player.show', $record->player) : null, true),
                Tables\Columns\TextColumn::make('reported_guid')->label('GUID')->limit(12)->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn ($state) => match($state) {
                    'pending'=>'warning','approved'=>'success','rejected'=>'gray',default=>'gray' }),
                Tables\Columns\TextColumn::make('evidence_count')->label('Shots')->counts('evidence')->alignCenter(),
                Tables\Columns\TextColumn::make('contact')->label('Contact')->limit(20)->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'])->default('pending'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Action::make('approve')
                    ->label('Approve → Flag')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve report and create cheat flag')
                    ->modalDescription(fn (TrackerPlayerReport $record) =>
                        'This creates a cheat flag for ' . ($record->player->name_clean ?? 'this player') .
                        ' and imports the reporter\'s screenshots as (private) evidence.')
                    ->form([
                        Forms\Components\TextInput::make('public_reason')
                            ->label('Public reason (shown on badge if you make it public later)')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('review_note')->label('Internal note (optional)')->rows(2)->maxLength(500),
                    ])
                    ->action(function (TrackerPlayerReport $record, array $data): void {
                        DB::transaction(function () use ($record, $data) {
                            $player = $record->player;
                            $ban = TrackerBan::create([
                                'player_id'        => $record->reported_player_id,
                                'guid_snapshot'    => $player?->real_guid_hash ?: $record->reported_guid,
                                'type'             => 'cheat',
                                'status'           => 'active',
                                'source'           => 'manual',
                                'reason'           => "From report #{$record->id} by " . ($record->reporter->name ?? 'user') . ":\n" . $record->description,
                                'public_reason'    => $data['public_reason'] ?? null,
                                'is_public'        => false,
                                'is_active'        => true,
                                'banned_by'        => auth()->id(),
                                'source_report_id' => null, // reports table is separate; we link via resulting_ban_id
                            ]);

                            // Import reporter screenshots as PRIVATE ban evidence
                            foreach ($record->evidence as $ev) {
                                TrackerBanEvidence::create([
                                    'ban_id'     => $ban->id,
                                    'type'       => 'screenshot',
                                    'file_path'  => $ev->file_path,
                                    'caption'    => 'From report #' . $record->id,
                                    'is_public'  => false,
                                    'uploaded_by'=> auth()->id(),
                                    'created_at' => now(),
                                ]);
                            }

                            $record->update([
                                'status'           => 'approved',
                                'reviewed_by'      => auth()->id(),
                                'reviewed_at'      => now(),
                                'review_note'      => $data['review_note'] ?? null,
                                'resulting_ban_id' => $ban->id,
                            ]);
                        });

                        Notification::make()->title('Report approved — cheat flag created')
                            ->body('Screenshots imported as private evidence. Review and set public when ready.')
                            ->success()->send();
                    })
                    ->visible(fn (TrackerPlayerReport $record) => $record->status === 'pending'),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject report')
                    ->form([
                        Forms\Components\Textarea::make('review_note')->label('Reason (required)')->required()->rows(2)->maxLength(500),
                    ])
                    ->action(function (TrackerPlayerReport $record, array $data): void {
                        $record->update([
                            'status'      => 'rejected',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                            'review_note' => $data['review_note'],
                        ]);
                        Notification::make()->title('Report rejected')->warning()->send();
                    })
                    ->visible(fn (TrackerPlayerReport $record) => $record->status === 'pending'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlayerReports::route('/'),
            'view'  => Pages\ViewPlayerReport::route('/{record}'),
        ];
    }
}
