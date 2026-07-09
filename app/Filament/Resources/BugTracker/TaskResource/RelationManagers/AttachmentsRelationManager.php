<?php

namespace App\Filament\Resources\BugTracker\TaskResource\RelationManagers;

use Filament\Tables\Columns\TextColumn;
use Filament\Actions\DeleteAction;
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
                TextColumn::make('original_filename')->searchable(),
                TextColumn::make('mime_type')->color('gray')->size('sm'),
                TextColumn::make('size_bytes')->formatStateUsing(fn ($state) =>
                    $state ? number_format($state / 1024, 1).' KB' : '—'),
                TextColumn::make('uploader.name')->placeholder('—'),
                TextColumn::make('created_at')->since(),
            ])
            ->recordActions([
                DeleteAction::make(),
            ]);
    }
}
