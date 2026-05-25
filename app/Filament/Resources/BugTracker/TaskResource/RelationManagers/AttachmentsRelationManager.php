<?php

namespace App\Filament\Resources\BugTracker\TaskResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';
    protected static ?string $recordTitleAttribute = 'original_filename';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('original_filename')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('original_filename')->searchable(),
                Tables\Columns\TextColumn::make('mime_type')->color('gray')->size('sm'),
                Tables\Columns\TextColumn::make('size_bytes')->formatStateUsing(fn ($state) =>
                    $state ? number_format($state / 1024, 1).' KB' : '—'),
                Tables\Columns\TextColumn::make('uploader.name')->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')->since(),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
