<?php
namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\HtmlString;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use App\Filament\Resources\TrackerClanResource\Pages\ListTrackerClans;
use App\Filament\Resources\TrackerClanResource\Pages\EditTrackerClan;
use App\Filament\Resources\TrackerClanResource\Pages;
use App\Models\Tracker\TrackerClan;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TrackerClanResource extends Resource
{
    protected static ?string $model = TrackerClan::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-flag';
    protected static string | \UnitEnum | null $navigationGroup = 'Clans';
    protected static ?string $navigationLabel = 'Tracker Clans (auto)';
    protected static ?int $navigationSort = 3;

    /**
     * Tracker clans are created by the auto-detection service, not by admins.
     * Direct creation is disabled to keep that pipeline clean.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Identity')->schema([
                TextInput::make('tag')->required()->maxLength(50)
                    ->helperText('Display tag as seen in player names (e.g. [RoG], =RoG=).'),
                TextInput::make('tag_clean')->required()->maxLength(50)
                    ->helperText('Stripped tag used for matching/searching (e.g. RoG).'),
                TextInput::make('name')->maxLength(255)
                    ->helperText('Optional full clan name.'),
            ])->columns(3),

            Section::make('Status')->schema([
                Select::make('status')->options([
                    'active'   => 'Active',
                    'inactive' => 'Inactive',
                    'merged'   => 'Merged',
                ])->required(),
                Toggle::make('is_verified')->label('Verified')->inline(false),
                Toggle::make('is_locked')->label('Locked')->inline(false)
                    ->helperText('Locked clans are NOT auto-updated by ClanDetectionService. Set automatically on claim approval.'),
            ])->columns(3),

            Section::make('Geo')->schema([
                TextInput::make('country')->maxLength(100),
                TextInput::make('country_code')->maxLength(2)
                    ->helperText('2-letter ISO code, e.g. DE, US, NL.'),
            ])->columns(2),

            Section::make('Profile (Detail Page)')->schema([
                Textarea::make('description')->rows(3)->columnSpanFull(),
                TextInput::make('website')->url(),
                TextInput::make('discord'),
                TextInput::make('clan_email')->email(),
            ])->columns(2),

            Section::make('Stats (read-only)')->schema([
                TextInput::make('member_count')->disabled()->dehydrated(false),
                TextInput::make('active_member_count')->disabled()->dehydrated(false),
                TextInput::make('avg_elo')->disabled()->dehydrated(false),
                TextInput::make('total_play_time_minutes')->disabled()->dehydrated(false)->label('Total playtime (min)'),
                TextInput::make('first_seen_at')->disabled()->dehydrated(false),
                TextInput::make('last_seen_at')->disabled()->dehydrated(false),
            ])->columns(3)->collapsible()->collapsed(),

            Section::make('Registered Clan Link')->schema([
                Placeholder::make('registered_clan')
                    ->label('')
                    ->content(function ($record) {
                        if (!$record) return '—';
                        $reg = $record->registeredClan;
                        if (!$reg) return new HtmlString('<span class="text-gray-500">No registered clan linked.</span>');
                        $owner = $reg->managers()->where('role', 'leader')->with('user')->first()?->user?->name ?? 'no owner';
                        $url = url('/admin/clans/' . $reg->id . '/edit');
                        return new HtmlString(
                            '<a href="' . $url . '" class="text-amber-500 hover:underline">' . e($reg->name) . '</a>'
                            . ' <span class="text-gray-400">(slug: ' . e($reg->slug) . ', owner: ' . e($owner) . ')</span>'
                        );
                    }),
            ])->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tag')->badge()->color('primary')->searchable(),
                TextColumn::make('tag_clean')->searchable()->sortable()->label('Clean'),
                TextColumn::make('name')->searchable()->limit(30)->placeholder('—'),
                TextColumn::make('country_code')->label('CC')->formatStateUsing(fn ($state) => $state ? strtoupper($state) : '—'),
                TextColumn::make('member_count')->numeric()->sortable()->label('Members'),
                TextColumn::make('active_member_count')->numeric()->sortable()->label('Active'),
                TextColumn::make('avg_elo')->numeric(0)->sortable()->label('ELO'),
                TextColumn::make('last_seen_at')->since()->sortable()->label('Last seen'),
                IconColumn::make('is_verified')->boolean()->label('Verif.'),
                IconColumn::make('is_locked')->boolean()->label('Locked'),
                IconColumn::make('has_registered')
                    ->label('Reg.')
                    ->getStateUsing(fn ($record) => $record->registeredClan()->exists())
                    ->boolean(),
                TextColumn::make('status')->badge()
                    ->color(fn ($state) => match($state) { 'active' => 'success', 'inactive' => 'gray', 'merged' => 'warning', default => 'gray' }),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'active' => 'Active', 'inactive' => 'Inactive', 'merged' => 'Merged',
                ]),
                TernaryFilter::make('is_locked')->label('Locked'),
                TernaryFilter::make('is_verified')->label('Verified'),
                Filter::make('has_registered')
                    ->label('Has registered clan')
                    ->query(fn ($query) => $query->whereHas('registeredClan'))
                    ->toggle(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('viewPublic')->label('View public')
                    ->icon('heroicon-o-globe-alt')->color('gray')
                    ->url(fn ($record) => route('tracker.clan.show', $record->id), shouldOpenInNewTab: true),
            ])
            ->toolbarActions([])
            ->defaultSort('member_count', 'desc');
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => ListTrackerClans::route('/'),
            'edit'  => EditTrackerClan::route('/{record}/edit'),
        ];
    }
}
