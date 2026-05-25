<?php

namespace App\Filament\Resources\BugTracker\ProjectResource\RelationManagers;

use App\Enums\BugTracker\TaskPriority;
use App\Enums\BugTracker\TaskStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';
    protected static ?string $recordTitleAttribute = 'title';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('last_activity_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('task_number')->label('#')->prefix('#')->color('gray')->size('sm'),
                Tables\Columns\TextColumn::make('title')->searchable()->limit(50)->weight('medium'),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (TaskStatus $state) => $state->label())
                    ->color(fn (TaskStatus $state) => $state->color()),
                Tables\Columns\TextColumn::make('priority')->badge()
                    ->formatStateUsing(fn (TaskPriority $state) => $state->label())
                    ->color(fn (TaskPriority $state) => $state->color()),
                Tables\Columns\TextColumn::make('assignee.name')->placeholder('—'),
                Tables\Columns\TextColumn::make('last_activity_at')->since(),
            ])
            ->actions([
                Tables\Actions\Action::make('open')->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => route('filament.admin.resources.bug-tracker.tasks.edit', ['record' => $record])),
            ]);
    }
}
