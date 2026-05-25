<?php

namespace App\Filament\Resources\BugTracker\TaskResource\RelationManagers;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';
    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')->label('Author')
                ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                ->searchable()->default(fn () => auth()->id()),
            Forms\Components\Textarea::make('body')->required()->rows(6)->columnSpanFull()
                ->helperText('Markdown supported'),
            Forms\Components\Toggle::make('is_internal')->label('Internal note')
                ->helperText('Only visible to admins'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->placeholder(fn ($record) => $record->author_name ?? 'Anonymous'),
                Tables\Columns\TextColumn::make('body')->limit(80)->wrap(),
                Tables\Columns\IconColumn::make('is_internal')->boolean()->label('Internal'),
                Tables\Columns\TextColumn::make('created_at')->since(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
