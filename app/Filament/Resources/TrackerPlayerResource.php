<?php

namespace App\Filament\Resources;

use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use App\Filament\Resources\TrackerPlayerResource\RelationManagers\AliasesRelationManager;
use App\Filament\Resources\TrackerPlayerResource\Pages\ListTrackerPlayers;
use App\Filament\Resources\TrackerPlayerResource\Pages\ViewTrackerPlayer;
use App\Filament\Resources\TrackerPlayerResource\Pages;
use App\Models\Tracker\TrackerPlayer;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only admin view of tracked players.
 *
 * Intentionally has NO create/edit/delete: player records are owned by the
 * tracker pipeline (Poller + Enhanced) and the merge/identity logic. Editing
 * name/name_clean/name_html here would break Decision A and alias matching.
 * This resource exists so admins can SEE a player's GUIDs, stats and aliases.
 */
class TrackerPlayerResource extends Resource
{
    protected static ?string $model = TrackerPlayer::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user';
    protected static string | \UnitEnum | null $navigationGroup = 'Tracker';
    protected static ?string $navigationLabel = 'Players (read-only)';
    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }

    public static function getEloquentQuery(): Builder
    {
        // Hide merged-away ghosts by default; they are noise for admins.
        return parent::getEloquentQuery()->whereNull('merged_into');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_seen_at', 'desc')
            ->columns([
                ImageColumn::make('avatar_url')
                    ->label('')->circular()->size(28)
                    ->defaultImageUrl(fn () => null),

                TextColumn::make('name_html')
                    ->label('Name')
                    ->formatStateUsing(fn ($state, $record) => $state ?: e($record->name_clean ?: 'Unknown'))
                    ->html()
                    ->searchable(['name_clean', 'name'])
                    ->description(fn ($record) => $record->name_clean)
                    ->wrap(),

                TextColumn::make('real_guid_hash')
                    ->label('Real GUID')
                    ->formatStateUsing(fn ($state) => $state ? substr($state, 0, 16) . '…' : '—')
                    ->copyableState(fn ($record) => $record->real_guid_hash)
                    ->copyable()
                    ->fontFamily('mono')->size('xs')
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->tooltip(fn ($record) => $record->real_guid_hash ?: 'No real GUID (getstatus-only server)')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('guid_hash')
                    ->label('Poller hash')
                    ->formatStateUsing(fn ($state) => $state ? substr($state, 0, 12) . '…' : '—')
                    ->copyableState(fn ($record) => $record->guid_hash)
                    ->copyable()
                    ->fontFamily('mono')->size('xs')->color('gray')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('country_code')
                    ->label('Country')->badge()->placeholder('—')->toggleable(),

                TextColumn::make('elo_rating')
                    ->label('ELO')->numeric(0)->sortable()
                    ->color('warning')->alignEnd(),

                TextColumn::make('total_sessions')
                    ->label('Sess')->numeric()->sortable()->alignEnd()->toggleable(),

                IconColumn::make('has_enhanced_data')
                    ->label('Enh')->boolean()->toggleable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success', 'banned' => 'danger', default => 'gray',
                    })
                    ->toggleable(),

                IconColumn::make('is_bot')
                    ->label('Bot')->boolean()
                    ->trueIcon('heroicon-o-cpu-chip')->falseIcon('heroicon-o-user')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('last_seen_at')
                    ->label('Last seen')->since()->sortable()->alignEnd(),
            ])
            ->filters([
                TernaryFilter::make('has_enhanced_data')->label('Enhanced data'),
                TernaryFilter::make('is_bot')->label('Bot'),
                Filter::make('has_real_guid')
                    ->label('Has real GUID')
                    ->query(fn (Builder $q) => $q->whereNotNull('real_guid_hash')),
                SelectFilter::make('status')->options([
                    'active' => 'Active', 'banned' => 'Banned', 'inactive' => 'Inactive',
                ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identity')->schema([
                TextEntry::make('name_html')
                    ->label('Name')->html()
                    ->formatStateUsing(fn ($state, $record) => $state ?: e($record->name_clean ?: 'Unknown')),
                TextEntry::make('name_clean')->label('Clean name')->copyable(),
                TextEntry::make('id')->label('Internal ID')->copyable(),

                TextEntry::make('real_guid_hash')
                    ->label('Real GUID (Enhanced)')
                    ->placeholder('— none (getstatus-only) —')
                    ->copyable()->fontFamily('mono')
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->helperText('Stable cross-server identity from sv_tracker2. Anchors bans & merges.'),

                TextEntry::make('guid_hash')
                    ->label('Poller hash')
                    ->placeholder('—')
                    ->copyable()->fontFamily('mono')->color('gray')
                    ->helperText('Derived from getstatus side. Not stable across name changes.'),
            ])->columns(2),

            Section::make('Account & Clan')->schema([
                TextEntry::make('display_name')->placeholder('—'),
                IconEntry::make('is_verified')->label('Verified')->boolean(),
                TextEntry::make('user.name')->label('Claimed by')->placeholder('— unclaimed —'),
                TextEntry::make('country')->placeholder('—'),
                TextEntry::make('active_clan.tag')
                    ->label('Active clan')
                    ->formatStateUsing(fn ($state, $record) => $record->active_clan?->name
                        ? $record->active_clan->tag . ' — ' . $record->active_clan->name
                        : ($record->active_clan?->tag ?? '—'))
                    ->placeholder('—'),
            ])->columns(2),

            Section::make('Stats')->schema([
                TextEntry::make('elo_rating')->label('ELO')->numeric(2)->color('warning'),
                TextEntry::make('elo_peak')->label('ELO peak')->numeric(2),
                TextEntry::make('total_sessions')->label('Sessions')->numeric(),
                TextEntry::make('play_time_formatted')->label('Playtime'),
                TextEntry::make('total_xp')->label('XP (score)')->numeric(),
                TextEntry::make('xp_per_hour')->label('XP / h')->numeric(),
                TextEntry::make('first_seen_at')->dateTime()->placeholder('—'),
                TextEntry::make('last_seen_at')->since()->placeholder('—'),
            ])->columns(4),

            Section::make('Enhanced Tracker')->schema([
                IconEntry::make('has_enhanced_data')->label('Has enhanced data')->boolean(),
                TextEntry::make('enhanced_total_kills')->label('Kills')->numeric()->placeholder('—'),
                TextEntry::make('enhanced_total_deaths')->label('Deaths')->numeric()->placeholder('—'),
                TextEntry::make('enhanced_total_headshots')->label('Headshots')->numeric()->placeholder('—'),
                TextEntry::make('enhanced_match_count')->label('Matches')->numeric()->placeholder('—'),
            ])->columns(5)
              ->visible(fn ($record) => (bool) $record->has_enhanced_data),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            AliasesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrackerPlayers::route('/'),
            'view'  => ViewTrackerPlayer::route('/{record}'),
        ];
    }
}
