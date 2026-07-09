<?php

namespace App\Filament\Resources\BugTracker\TaskResource\RelationManagers;

use Filament\Tables\Columns\TextColumn;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class HistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'history';
    protected static ?string $recordTitleAttribute = 'field';
    protected static ?string $title = 'History';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('field')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->since(),
                TextColumn::make('user.name')->placeholder('System')->color('gray'),
                TextColumn::make('field')->badge(),
                TextColumn::make('old_value')->limit(40)->placeholder('—'),
                TextColumn::make('new_value')->limit(40)->placeholder('—'),
            ]);
    }
}
