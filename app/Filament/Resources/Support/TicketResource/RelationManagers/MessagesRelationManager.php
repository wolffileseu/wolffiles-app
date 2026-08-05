<?php

namespace App\Filament\Resources\Support\TicketResource\RelationManagers;

use App\Enums\Support\AuthorType;
use App\Enums\Support\SyncStatus;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';
    protected static ?string $title = 'Conversation';
    protected static ?string $recordTitleAttribute = 'id';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('body')->label('Message')->required()->rows(8)->columnSpanFull(),
            Toggle::make('is_internal')->label('Internal note')
                ->helperText('Internal notes are never sent to Discord or e-mail'),

            // Antworten aus dem Panel sind immer Staff-Antworten.
            Hidden::make('author_type')->default(AuthorType::Staff->value),
            Hidden::make('user_id')->default(fn () => auth()->id()),
            Hidden::make('source')->default('admin'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->defaultSort('created_at', 'asc')
            ->columns([
                TextColumn::make('author_type')->label('By')->badge()
                    ->formatStateUsing(fn (AuthorType $state) => $state->label())
                    ->color(fn (AuthorType $state) => $state->color()),
                TextColumn::make('author_label')->label('Author')->limit(24),
                TextColumn::make('body')->limit(90)->wrap(),
                IconColumn::make('is_internal')->boolean()->label('Internal'),
                TextColumn::make('sync_status')->label('Delivery')->badge()
                    ->formatStateUsing(fn (SyncStatus $state) => $state->label())
                    ->color(fn (SyncStatus $state) => $state->color())
                    ->tooltip(fn ($record) => $record->sync_error),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->headerActions([
                CreateAction::make()->label('Reply'),
            ])
            ->recordActions([
                DeleteAction::make(),
            ]);
    }
}
