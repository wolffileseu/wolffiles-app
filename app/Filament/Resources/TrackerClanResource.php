<?php
namespace App\Filament\Resources;

use App\Filament\Resources\TrackerClanResource\Pages;
use App\Models\Tracker\TrackerClan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TrackerClanResource extends Resource
{
    protected static ?string $model = TrackerClan::class;
    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationGroup = 'Clans';
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

    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Identity')->schema([
                Forms\Components\TextInput::make('tag')->required()->maxLength(50)
                    ->helperText('Display tag as seen in player names (e.g. [RoG], =RoG=).'),
                Forms\Components\TextInput::make('tag_clean')->required()->maxLength(50)
                    ->helperText('Stripped tag used for matching/searching (e.g. RoG).'),
                Forms\Components\TextInput::make('name')->maxLength(255)
                    ->helperText('Optional full clan name.'),
            ])->columns(3),

            Forms\Components\Section::make('Status')->schema([
                Forms\Components\Select::make('status')->options([
                    'active'   => 'Active',
                    'inactive' => 'Inactive',
                    'merged'   => 'Merged',
                ])->required(),
                Forms\Components\Toggle::make('is_verified')->label('Verified')->inline(false),
                Forms\Components\Toggle::make('is_locked')->label('Locked')->inline(false)
                    ->helperText('Locked clans are NOT auto-updated by ClanDetectionService. Set automatically on claim approval.'),
            ])->columns(3),

            Forms\Components\Section::make('Geo')->schema([
                Forms\Components\TextInput::make('country')->maxLength(100),
                Forms\Components\TextInput::make('country_code')->maxLength(2)
                    ->helperText('2-letter ISO code, e.g. DE, US, NL.'),
            ])->columns(2),

            Forms\Components\Section::make('Profile (Detail Page)')->schema([
                Forms\Components\Textarea::make('description')->rows(3)->columnSpanFull(),
                Forms\Components\TextInput::make('website')->url(),
                Forms\Components\TextInput::make('discord'),
                Forms\Components\TextInput::make('clan_email')->email(),
            ])->columns(2),

            Forms\Components\Section::make('Stats (read-only)')->schema([
                Forms\Components\TextInput::make('member_count')->disabled()->dehydrated(false),
                Forms\Components\TextInput::make('active_member_count')->disabled()->dehydrated(false),
                Forms\Components\TextInput::make('avg_elo')->disabled()->dehydrated(false),
                Forms\Components\TextInput::make('total_play_time_minutes')->disabled()->dehydrated(false)->label('Total playtime (min)'),
                Forms\Components\TextInput::make('first_seen_at')->disabled()->dehydrated(false),
                Forms\Components\TextInput::make('last_seen_at')->disabled()->dehydrated(false),
            ])->columns(3)->collapsible()->collapsed(),

            Forms\Components\Section::make('Registered Clan Link')->schema([
                Forms\Components\Placeholder::make('registered_clan')
                    ->label('')
                    ->content(function ($record) {
                        if (!$record) return '—';
                        $reg = $record->registeredClan;
                        if (!$reg) return new \Illuminate\Support\HtmlString('<span class="text-gray-500">No registered clan linked.</span>');
                        $owner = $reg->managers()->where('role', 'owner')->with('user')->first()?->user?->name ?? 'no owner';
                        $url = url('/admin/clans/' . $reg->id . '/edit');
                        return new \Illuminate\Support\HtmlString(
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
                Tables\Columns\TextColumn::make('tag')->badge()->color('primary')->searchable(),
                Tables\Columns\TextColumn::make('tag_clean')->searchable()->sortable()->label('Clean'),
                Tables\Columns\TextColumn::make('name')->searchable()->limit(30)->placeholder('—'),
                Tables\Columns\TextColumn::make('country_code')->label('CC')->formatStateUsing(fn ($state) => $state ? strtoupper($state) : '—'),
                Tables\Columns\TextColumn::make('member_count')->numeric()->sortable()->label('Members'),
                Tables\Columns\TextColumn::make('active_member_count')->numeric()->sortable()->label('Active'),
                Tables\Columns\TextColumn::make('avg_elo')->numeric(0)->sortable()->label('ELO'),
                Tables\Columns\TextColumn::make('last_seen_at')->since()->sortable()->label('Last seen'),
                Tables\Columns\IconColumn::make('is_verified')->boolean()->label('Verif.'),
                Tables\Columns\IconColumn::make('is_locked')->boolean()->label('Locked'),
                Tables\Columns\IconColumn::make('has_registered')
                    ->label('Reg.')
                    ->getStateUsing(fn ($record) => $record->registeredClan()->exists())
                    ->boolean(),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn ($state) => match($state) { 'active' => 'success', 'inactive' => 'gray', 'merged' => 'warning', default => 'gray' }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'active' => 'Active', 'inactive' => 'Inactive', 'merged' => 'Merged',
                ]),
                Tables\Filters\TernaryFilter::make('is_locked')->label('Locked'),
                Tables\Filters\TernaryFilter::make('is_verified')->label('Verified'),
                Tables\Filters\Filter::make('has_registered')
                    ->label('Has registered clan')
                    ->query(fn ($query) => $query->whereHas('registeredClan'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('viewPublic')->label('View public')
                    ->icon('heroicon-o-globe-alt')->color('gray')
                    ->url(fn ($record) => route('tracker.clan.show', $record->id), shouldOpenInNewTab: true),
            ])
            ->bulkActions([])
            ->defaultSort('member_count', 'desc');
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrackerClans::route('/'),
            'edit'  => Pages\EditTrackerClan::route('/{record}/edit'),
        ];
    }
}
