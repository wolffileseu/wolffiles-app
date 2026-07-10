<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\PollResource\Pages\ListPolls;
use App\Filament\Resources\PollResource\Pages\CreatePoll;
use App\Filament\Resources\PollResource\Pages\EditPoll;
use App\Filament\Resources\PollResource\Pages;
use App\Models\Poll;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PollResource extends Resource
{
    protected static ?string $model = Poll::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';
    protected static string | \UnitEnum | null $navigationGroup = 'Content';
    protected static ?int $navigationSort = 5;



    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('question')->required()->maxLength(500)->columnSpanFull(),
            Toggle::make('is_active')->default(true),
            Toggle::make('multiple_choice')->default(false),
            DateTimePicker::make('ends_at')->nullable(),
            Repeater::make('options')
                ->relationship()
                ->schema([
                    TextInput::make('text')->required()->maxLength(255),
                    TextInput::make('sort_order')->numeric()->default(0),
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
                TextColumn::make('id')->sortable(),
                TextColumn::make('question')->limit(50)->searchable(),
                TextColumn::make('options_count')->counts('options')->label('Options'),
                TextColumn::make('votes_count')->counts('votes')->label('Votes'),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('ends_at')->dateTime('d.m.Y H:i'),
                TextColumn::make('created_at')->dateTime('d.m.Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPolls::route('/'),
            'create' => CreatePoll::route('/create'),
            'edit' => EditPoll::route('/{record}/edit'),
        ];
    }
}
