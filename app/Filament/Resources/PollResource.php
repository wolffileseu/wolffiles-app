<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PollResource\Pages;
use App\Models\Poll;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PollResource extends Resource
{
    protected static ?string $model = Poll::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Content';
    protected static ?int $navigationSort = 5;


    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can('view_polls');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('question')->required()->maxLength(500)->columnSpanFull(),
            Forms\Components\Toggle::make('is_active')->default(true),
            Forms\Components\Toggle::make('multiple_choice')->default(false),
            Forms\Components\DateTimePicker::make('ends_at')->nullable(),
            Forms\Components\Repeater::make('options')
                ->relationship()
                ->schema([
                    Forms\Components\TextInput::make('text')->required()->maxLength(255),
                    Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
                ])
                ->minItems(2)
                ->maxItems(10)
                ->columnSpanFull()
                ->defaultItems(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('question')->limit(50)->searchable(),
                Tables\Columns\TextColumn::make('options_count')->counts('options')->label('Options'),
                Tables\Columns\TextColumn::make('votes_count')->counts('votes')->label('Votes'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('ends_at')->dateTime('d.m.Y H:i'),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d.m.Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPolls::route('/'),
            'create' => Pages\CreatePoll::route('/create'),
            'edit' => Pages\EditPoll::route('/{record}/edit'),
        ];
    }
}
