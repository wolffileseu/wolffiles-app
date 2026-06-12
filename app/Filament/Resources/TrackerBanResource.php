<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrackerBanResource\Pages;
use App\Models\Tracker\TrackerBan;
use App\Models\Tracker\TrackerPlayer;
use App\Models\Tracker\TrackerServer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class TrackerBanResource extends Resource
{
    protected static ?string $model = TrackerBan::class;
    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationGroup = 'Tracker';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationLabel = 'Cheat Flags / Bans';
    protected static ?string $modelLabel = 'Flag';
    protected static ?string $pluralModelLabel = 'Cheat Flags / Bans';

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can('view_tracker_bans');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Player')->schema([
                Forms\Components\Select::make('player_id')
                    ->label('Player')
                    ->required()
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search) {
                        return TrackerPlayer::query()
                            ->whereNull('merged_into')
                            ->where(function ($q) use ($search) {
                                $q->where('name_clean', 'like', "%{$search}%")
                                  ->orWhere('real_guid_hash', 'like', "%{$search}%");
                            })
                            ->limit(30)->get()
                            ->mapWithKeys(fn ($p) => [$p->id =>
                                ($p->name_clean ?: 'Unknown') .
                                ($p->real_guid_hash ? '  ['.substr($p->real_guid_hash,0,12).'…]' : '  [no GUID]')
                            ])->all();
                    })
                    ->getOptionLabelUsing(function ($value) {
                        $p = TrackerPlayer::find($value);
                        return $p ? ($p->name_clean ?: 'Unknown') : "#$value";
                    })
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        $p = TrackerPlayer::find($state);
                        $set('guid_snapshot', $p?->real_guid_hash);
                    }),

                Forms\Components\Placeholder::make('guid_warning')
                    ->label('')
                    ->content(function (Forms\Get $get) {
                        $pid = $get('player_id');
                        if (!$pid) return '';
                        $p = TrackerPlayer::find($pid);
                        if ($p && $p->real_guid_hash) {
                            return new \Illuminate\Support\HtmlString(
                                '<span style="color:#34d399">✓ Stable GUID anchor: '.e(substr($p->real_guid_hash,0,16)).'…</span>');
                        }
                        return new \Illuminate\Support\HtmlString(
                            '<span style="color:#fbbf24">⚠ No real GUID — this flag is anchored only to the profile and may be lost on a name change or merge.</span>');
                    }),

                Forms\Components\TextInput::make('guid_snapshot')
                    ->label('GUID snapshot (anchor)')
                    ->helperText('Frozen at save time. Auto-filled from the player\'s real GUID.')
                    ->maxLength(64),
            ])->columns(1),

            Forms\Components\Section::make('Flag')->schema([
                Forms\Components\Select::make('type')->required()->default('cheat')
                    ->options(['cheat'=>'Cheat','ban'=>'Ban','watch'=>'Watch','cleared'=>'Cleared']),
                Forms\Components\Select::make('status')->required()->default('active')
                    ->options(['pending'=>'Pending','active'=>'Active','lifted'=>'Lifted','appealed'=>'Appealed']),
                Forms\Components\Select::make('source')->required()->default('manual')
                    ->options(['manual'=>'Manual','anticheat'=>'Anticheat','vote'=>'Vote','imported'=>'Imported']),
                Forms\Components\DateTimePicker::make('occurred_at')->label('When did it happen'),
                Forms\Components\Textarea::make('reason')->label('Internal reason (admin only)')->rows(3)->columnSpanFull(),
                Forms\Components\TextInput::make('public_reason')->label('Public reason (shown on badge)')->maxLength(255)->columnSpanFull(),
                Forms\Components\Select::make('servers')
                    ->label('Servers where it happened')
                    ->multiple()->searchable()->preload()
                    ->relationship('servers', 'hostname_clean')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->hostname_clean ?: $record->hostname ?: "Server #{$record->id}"),
            ])->columns(2),

            Forms\Components\Section::make('Visibility')->schema([
                Forms\Components\Toggle::make('is_public')
                    ->label('Public badge')
                    ->helperText('Requires at least one PUBLIC evidence item. Add evidence in the Evidence tab first, then enable.')
                    ->rules([
                        fn (Forms\Get $get): \Closure => function (string $attr, $value, \Closure $fail) use ($get) {
                            if ($value) {
                                $banId = $get('id');
                                $hasPublic = $banId && \App\Models\Tracker\TrackerBanEvidence::where('ban_id',$banId)->where('is_public',true)->exists();
                                if (!$hasPublic) {
                                    $fail('Cannot make public: no public evidence attached yet. Add a public evidence item first.');
                                }
                            }
                        },
                    ]),
                Forms\Components\Toggle::make('is_active')->label('Active (legacy ban flag)')->default(true),
                Forms\Components\Hidden::make('banned_by')->default(fn () => auth()->id()),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('player.name_clean')->label('Player')->searchable()->limit(24)
                    ->url(fn ($record) => $record->player ? route('tracker.player.show', $record->player) : null, true),
                Tables\Columns\TextColumn::make('guid_snapshot')->label('GUID')->limit(16)->copyable()->color('gray')->size('sm')->toggleable(),
                Tables\Columns\TextColumn::make('type')->badge()->color(fn ($state) => match($state) {
                    'cheat'=>'danger','ban'=>'warning','watch'=>'info','cleared'=>'success',default=>'gray' }),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn ($state) => match($state) {
                    'active'=>'danger','pending'=>'warning','appealed'=>'info','lifted'=>'gray',default=>'gray' }),
                Tables\Columns\IconColumn::make('is_public')->label('Public')->boolean(),
                Tables\Columns\TextColumn::make('evidence_count')->label('Evidence')->counts('evidence')->alignCenter(),
                Tables\Columns\TextColumn::make('public_reason')->label('Public reason')->limit(30)->placeholder('—'),
                Tables\Columns\TextColumn::make('bannedBy.name')->label('By')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options(['cheat'=>'Cheat','ban'=>'Ban','watch'=>'Watch','cleared'=>'Cleared']),
                Tables\Filters\SelectFilter::make('status')->options(['pending'=>'Pending','active'=>'Active','lifted'=>'Lifted','appealed'=>'Appealed']),
                Tables\Filters\TernaryFilter::make('is_public')->label('Public'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TrackerBanResource\RelationManagers\EvidenceRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTrackerBans::route('/'),
            'create' => Pages\CreateTrackerBan::route('/create'),
            'edit'   => Pages\EditTrackerBan::route('/{record}/edit'),
        ];
    }
}
