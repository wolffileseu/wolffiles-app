<?php
namespace App\Filament\Resources\ClanResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Tracker\TrackerClanMember;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';
    protected static ?string $title = 'Members';

    public static function canViewForRecord(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): bool
    {
        // Only show this tab if the clan has a linked tracker_clan
        return $ownerRecord->trackerClan !== null;
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('player_id')
                ->relationship('player', 'name_clean')
                ->searchable()
                ->required(),
            Forms\Components\TextInput::make('role_label')->maxLength(50),
            Forms\Components\Select::make('squad_id')
                ->relationship('squad', 'name')
                ->searchable()
                ->nullable(),
            Forms\Components\Toggle::make('is_active')->default(true),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->where('clan_id', $this->ownerRecord->trackerClan?->id ?? 0))
            ->columns([
                Tables\Columns\TextColumn::make('player.name_clean')->label('Player')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('role_label')->label('Role')->placeholder('—'),
                Tables\Columns\TextColumn::make('squad.name')->label('Squad')->placeholder('—'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('joined_at')->since()->sortable(),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
            ])
            ->defaultSort('sort_order', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->placeholder('All')->trueLabel('Active')->falseLabel('Inactive'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->mutateFormDataUsing(function (array $data) {
                    $data['clan_id'] = $this->ownerRecord->trackerClan->id;
                    return $data;
                }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
