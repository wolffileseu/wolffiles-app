<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Closure;
use App\Models\Tracker\TrackerBanEvidence;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\TrackerBanResource\RelationManagers\EvidenceRelationManager;
use App\Filament\Resources\TrackerBanResource\Pages\ListTrackerBans;
use App\Filament\Resources\TrackerBanResource\Pages\CreateTrackerBan;
use App\Filament\Resources\TrackerBanResource\Pages\EditTrackerBan;
use App\Filament\Resources\TrackerBanResource\Pages;
use App\Models\Tracker\TrackerBan;
use App\Models\Tracker\TrackerPlayer;
use App\Models\Tracker\TrackerServer;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class TrackerBanResource extends Resource
{
    protected static ?string $model = TrackerBan::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-flag';
    protected static string | \UnitEnum | null $navigationGroup = 'Tracker';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationLabel = 'Cheat Flags / Bans';
    protected static ?string $modelLabel = 'Flag';
    protected static ?string $pluralModelLabel = 'Cheat Flags / Bans';


    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Player')->schema([
                Select::make('player_id')
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
                    ->afterStateUpdated(function ($state, Set $set) {
                        $p = TrackerPlayer::find($state);
                        $set('guid_snapshot', $p?->real_guid_hash);
                    }),

                Placeholder::make('guid_warning')
                    ->label('')
                    ->content(function (Get $get) {
                        $pid = $get('player_id');
                        if (!$pid) return '';
                        $p = TrackerPlayer::find($pid);
                        if ($p && $p->real_guid_hash) {
                            return new HtmlString(
                                '<span style="color:#34d399">✓ Stable GUID anchor: '.e(substr($p->real_guid_hash,0,16)).'…</span>');
                        }
                        return new HtmlString(
                            '<span style="color:#fbbf24">⚠ No real GUID — this flag is anchored only to the profile and may be lost on a name change or merge.</span>');
                    }),

                TextInput::make('guid_snapshot')
                    ->label('GUID snapshot (anchor)')
                    ->helperText('Frozen at save time. Auto-filled from the player\'s real GUID.')
                    ->maxLength(64),
            ])->columns(1),

            Section::make('Flag')->schema([
                Select::make('type')->required()->default('cheat')
                    ->options(['cheat'=>'Cheat','ban'=>'Ban','watch'=>'Watch','cleared'=>'Cleared']),
                Select::make('status')->required()->default('active')
                    ->options(['pending'=>'Pending','active'=>'Active','lifted'=>'Lifted','appealed'=>'Appealed']),
                Select::make('source')->required()->default('manual')
                    ->options(['manual'=>'Manual','anticheat'=>'Anticheat','vote'=>'Vote','imported'=>'Imported']),
                DateTimePicker::make('occurred_at')->label('When did it happen'),
                Textarea::make('reason')->label('Internal reason (admin only)')->rows(3)->columnSpanFull(),
                TextInput::make('public_reason')->label('Public reason (shown on badge)')->maxLength(255)->columnSpanFull(),
                Select::make('servers')
                    ->label('Servers where it happened')
                    ->multiple()->searchable()->preload()
                    ->relationship('servers', 'hostname_clean')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->hostname_clean ?: $record->hostname ?: "Server #{$record->id}"),
            ])->columns(2),

            Section::make('Visibility')->schema([
                Toggle::make('is_public')
                    ->label('Public badge')
                    ->helperText('Requires at least one PUBLIC evidence item. Add evidence in the Evidence tab first, then enable.')
                    ->rules([
                        fn (Get $get): Closure => function (string $attr, $value, Closure $fail) use ($get) {
                            if ($value) {
                                $banId = $get('id');
                                $hasPublic = $banId && TrackerBanEvidence::where('ban_id',$banId)->where('is_public',true)->exists();
                                if (!$hasPublic) {
                                    $fail('Cannot make public: no public evidence attached yet. Add a public evidence item first.');
                                }
                            }
                        },
                    ]),
                Toggle::make('is_active')->label('Active (legacy ban flag)')->default(true),
                Hidden::make('banned_by')->default(fn () => auth()->id()),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('player.name_clean')->label('Player')->searchable()->limit(24)
                    ->url(fn ($record) => $record->player ? route('tracker.player.show', $record->player) : null, true),
                TextColumn::make('guid_snapshot')->label('GUID')->limit(16)->copyable()->color('gray')->size('sm')->toggleable(),
                TextColumn::make('type')->badge()->color(fn ($state) => match($state) {
                    'cheat'=>'danger','ban'=>'warning','watch'=>'info','cleared'=>'success',default=>'gray' }),
                TextColumn::make('status')->badge()->color(fn ($state) => match($state) {
                    'active'=>'danger','pending'=>'warning','appealed'=>'info','lifted'=>'gray',default=>'gray' }),
                IconColumn::make('is_public')->label('Public')->boolean(),
                TextColumn::make('evidence_count')->label('Evidence')->counts('evidence')->alignCenter(),
                TextColumn::make('public_reason')->label('Public reason')->limit(30)->placeholder('—'),
                TextColumn::make('bannedBy.name')->label('By')->placeholder('—')->toggleable(),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->options(['cheat'=>'Cheat','ban'=>'Ban','watch'=>'Watch','cleared'=>'Cleared']),
                SelectFilter::make('status')->options(['pending'=>'Pending','active'=>'Active','lifted'=>'Lifted','appealed'=>'Appealed']),
                TernaryFilter::make('is_public')->label('Public'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            EvidenceRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListTrackerBans::route('/'),
            'create' => CreateTrackerBan::route('/create'),
            'edit'   => EditTrackerBan::route('/{record}/edit'),
        ];
    }
}
