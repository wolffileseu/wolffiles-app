<?php

namespace App\Filament\Resources\TrackerPlayerResource\RelationManagers;

use Filament\Tables\Columns\TextColumn;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Read-only list of a player's known aliases (every distinct name they have
 * used, with usage count). Populated by PlayerAliasHandler in the pipeline,
 * so no create/edit/delete here.
 */
class AliasesRelationManager extends RelationManager
{
    protected static string $relationship = 'aliases';
    protected static ?string $title = 'Aliases (names used)';
    protected static string | \BackedEnum | null $icon = 'heroicon-o-identification';

    public function isReadOnly(): bool { return true; }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('times_used', 'desc')
            ->columns([
                TextColumn::make('name_html')
                    ->label('Name')
                    ->formatStateUsing(fn ($state, $record) => $state ?: e($record->name_clean ?: $record->name ?: '—'))
                    ->html()
                    ->description(fn ($record) => $record->name_clean)
                    ->wrap(),

                TextColumn::make('times_used')
                    ->label('Used')->numeric()->sortable()->alignEnd()
                    ->badge()->color('gray'),

                TextColumn::make('first_seen_at')
                    ->label('First seen')->dateTime()->sortable()->placeholder('—')->toggleable(),

                TextColumn::make('last_seen_at')
                    ->label('Last seen')->since()->sortable()->placeholder('—'),
            ])
            ->paginated([10, 25, 50]);
    }
}
