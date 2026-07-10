<?php
namespace App\Filament\Resources\ClanResource\RelationManagers;

use Illuminate\Database\Eloquent\Model;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Tracker\TrackerClanMember;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';
    protected static ?string $title = 'Members';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        // Only show this tab if the clan has a linked tracker_clan
        return $ownerRecord->trackerClan !== null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('player_id')
                ->relationship('player', 'name_clean')
                ->searchable()
                ->required(),
            TextInput::make('role_label')->maxLength(50),
            Select::make('squad_id')
                ->relationship('squad', 'name')
                ->searchable()
                ->nullable(),
            Toggle::make('is_active')->default(true),
            TextInput::make('sort_order')->numeric()->default(0),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->where('clan_id', $this->ownerRecord->trackerClan?->id ?? 0))
            ->columns([
                TextColumn::make('player.name_clean')->label('Player')->searchable()->sortable(),
                TextColumn::make('role_label')->label('Role')->placeholder('—'),
                TextColumn::make('squad.name')->label('Squad')->placeholder('—'),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('joined_at')->since()->sortable(),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->defaultSort('sort_order', 'asc')
            ->filters([
                TernaryFilter::make('is_active')->placeholder('All')->trueLabel('Active')->falseLabel('Inactive'),
            ])
            ->headerActions([
                CreateAction::make()->mutateDataUsing(function (array $data) {
                    $data['clan_id'] = $this->ownerRecord->trackerClan->id;
                    return $data;
                }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
