<?php

namespace App\Filament\Resources\BugTracker\TaskResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';
    protected static ?string $recordTitleAttribute = 'id';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')->label('Author')
                ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                ->searchable()->default(fn () => auth()->id()),
            Textarea::make('body')->required()->rows(6)->columnSpanFull()
                ->helperText('Markdown supported'),
            Toggle::make('is_internal')->label('Internal note')
                ->helperText('Only visible to admins'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')->placeholder(fn ($record) => $record->author_name ?? 'Anonymous'),
                TextColumn::make('body')->limit(80)->wrap(),
                IconColumn::make('is_internal')->boolean()->label('Internal'),
                TextColumn::make('created_at')->since(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
