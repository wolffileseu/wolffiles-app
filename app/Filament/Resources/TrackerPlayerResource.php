<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrackerPlayerResource\Pages;
use App\Models\Tracker\TrackerPlayer;
use Filament\Infolists;
use Filament\Infolists\Infolist;
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
    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationGroup = 'Tracker';
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
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('')->circular()->size(28)
                    ->defaultImageUrl(fn () => null),

                Tables\Columns\TextColumn::make('name_html')
                    ->label('Name')
                    ->formatStateUsing(fn ($state, $record) => $state ?: e($record->name_clean ?: 'Unknown'))
                    ->html()
                    ->searchable(['name_clean', 'name'])
                    ->description(fn ($record) => $record->name_clean)
                    ->wrap(),

                Tables\Columns\TextColumn::make('real_guid_hash')
                    ->label('Real GUID')
                    ->formatStateUsing(fn ($state) => $state ? substr($state, 0, 16) . '…' : '—')
                    ->copyableState(fn ($record) => $record->real_guid_hash)
                    ->copyable()
                    ->fontFamily('mono')->size('xs')
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->tooltip(fn ($record) => $record->real_guid_hash ?: 'No real GUID (getstatus-only server)')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('guid_hash')
                    ->label('Poller hash')
                    ->formatStateUsing(fn ($state) => $state ? substr($state, 0, 12) . '…' : '—')
                    ->copyableState(fn ($record) => $record->guid_hash)
                    ->copyable()
                    ->fontFamily('mono')->size('xs')->color('gray')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('country_code')
                    ->label('Country')->badge()->placeholder('—')->toggleable(),

                Tables\Columns\TextColumn::make('elo_rating')
                    ->label('ELO')->numeric(0)->sortable()
                    ->color('warning')->alignEnd(),

                Tables\Columns\TextColumn::make('total_sessions')
                    ->label('Sess')->numeric()->sortable()->alignEnd()->toggleable(),

                Tables\Columns\IconColumn::make('has_enhanced_data')
                    ->label('Enh')->boolean()->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success', 'banned' => 'danger', default => 'gray',
                    })
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_bot')
                    ->label('Bot')->boolean()
                    ->trueIcon('heroicon-o-cpu-chip')->falseIcon('heroicon-o-user')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('last_seen_at')
                    ->label('Last seen')->since()->sortable()->alignEnd(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('has_enhanced_data')->label('Enhanced data'),
                Tables\Filters\TernaryFilter::make('is_bot')->label('Bot'),
                Tables\Filters\Filter::make('has_real_guid')
                    ->label('Has real GUID')
                    ->query(fn (Builder $q) => $q->whereNotNull('real_guid_hash')),
                Tables\Filters\SelectFilter::make('status')->options([
                    'active' => 'Active', 'banned' => 'Banned', 'inactive' => 'Inactive',
                ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Identity')->schema([
                Infolists\Components\TextEntry::make('name_html')
                    ->label('Name')->html()
                    ->formatStateUsing(fn ($state, $record) => $state ?: e($record->name_clean ?: 'Unknown')),
                Infolists\Components\TextEntry::make('name_clean')->label('Clean name')->copyable(),
                Infolists\Components\TextEntry::make('id')->label('Internal ID')->copyable(),

                Infolists\Components\TextEntry::make('real_guid_hash')
                    ->label('Real GUID (Enhanced)')
                    ->placeholder('— none (getstatus-only) —')
                    ->copyable()->fontFamily('mono')
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->helperText('Stable cross-server identity from sv_tracker2. Anchors bans & merges.'),

                Infolists\Components\TextEntry::make('guid_hash')
                    ->label('Poller hash')
                    ->placeholder('—')
                    ->copyable()->fontFamily('mono')->color('gray')
                    ->helperText('Derived from getstatus side. Not stable across name changes.'),
            ])->columns(2),

            Infolists\Components\Section::make('Account & Clan')->schema([
                Infolists\Components\TextEntry::make('display_name')->placeholder('—'),
                Infolists\Components\IconEntry::make('is_verified')->label('Verified')->boolean(),
                Infolists\Components\TextEntry::make('user.name')->label('Claimed by')->placeholder('— unclaimed —'),
                Infolists\Components\TextEntry::make('country')->placeholder('—'),
                Infolists\Components\TextEntry::make('active_clan.tag')
                    ->label('Active clan')
                    ->formatStateUsing(fn ($state, $record) => $record->active_clan?->name
                        ? $record->active_clan->tag . ' — ' . $record->active_clan->name
                        : ($record->active_clan?->tag ?? '—'))
                    ->placeholder('—'),
            ])->columns(2),

            Infolists\Components\Section::make('Stats')->schema([
                Infolists\Components\TextEntry::make('elo_rating')->label('ELO')->numeric(2)->color('warning'),
                Infolists\Components\TextEntry::make('elo_peak')->label('ELO peak')->numeric(2),
                Infolists\Components\TextEntry::make('total_sessions')->label('Sessions')->numeric(),
                Infolists\Components\TextEntry::make('play_time_formatted')->label('Playtime'),
                Infolists\Components\TextEntry::make('total_xp')->label('XP (score)')->numeric(),
                Infolists\Components\TextEntry::make('xp_per_hour')->label('XP / h')->numeric(),
                Infolists\Components\TextEntry::make('first_seen_at')->dateTime()->placeholder('—'),
                Infolists\Components\TextEntry::make('last_seen_at')->since()->placeholder('—'),
            ])->columns(4),

            Infolists\Components\Section::make('Enhanced Tracker')->schema([
                Infolists\Components\IconEntry::make('has_enhanced_data')->label('Has enhanced data')->boolean(),
                Infolists\Components\TextEntry::make('enhanced_total_kills')->label('Kills')->numeric()->placeholder('—'),
                Infolists\Components\TextEntry::make('enhanced_total_deaths')->label('Deaths')->numeric()->placeholder('—'),
                Infolists\Components\TextEntry::make('enhanced_total_headshots')->label('Headshots')->numeric()->placeholder('—'),
                Infolists\Components\TextEntry::make('enhanced_match_count')->label('Matches')->numeric()->placeholder('—'),
            ])->columns(5)
              ->visible(fn ($record) => (bool) $record->has_enhanced_data),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            TrackerPlayerResource\RelationManagers\AliasesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrackerPlayers::route('/'),
            'view'  => Pages\ViewTrackerPlayer::route('/{record}'),
        ];
    }
}
