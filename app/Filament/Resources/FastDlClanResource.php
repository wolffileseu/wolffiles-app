<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\CheckboxList;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use App\Filament\Resources\FastDlClanResource\Pages\ListFastDlClans;
use App\Filament\Resources\FastDlClanResource\Pages\CreateFastDlClan;
use App\Filament\Resources\FastDlClanResource\Pages\EditFastDlClan;
use App\Filament\Resources\FastDlClanResource\Pages;
use App\Models\FastDl\FastDlClan;
use App\Models\FastDl\FastDlGame;
use App\Models\FastDl\FastDlDirectory;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FastDlClanResource extends Resource
{
    protected static ?string $model = FastDlClan::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';
    protected static string | \UnitEnum | null $navigationGroup = 'Fast Download';
    protected static ?string $navigationLabel = 'Clans';
    protected static ?int $navigationSort = 4;


    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Clan')->schema([
                TextInput::make('name')->required(),
                TextInput::make('slug')->required()->unique(ignoreRecord: true)
                    ->helperText('URL: dl.wolffiles.eu/{slug}/'),
                Select::make('game_id')
                    ->label('Game')
                    ->options(FastDlGame::where('is_active', true)->pluck('name', 'id'))
                    ->required(),
                Select::make('leader_user_id')
                    ->label('Clan Leader')
                    ->relationship('leader', 'name')
                    ->searchable()
                    ->nullable(),
                Select::make('admins')
                    ->label('Co-Admins')
                    ->relationship('admins', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->helperText('Additional users who may manage this Fast Download. The leader is always included.')
                    ->columnSpanFull(),
                Toggle::make('include_base')->default(true)
                    ->helperText('Auto-include base directory (etmain) with all maps'),
                Toggle::make('is_active')->default(true),
                TextInput::make('storage_limit_mb')
                    ->label('Storage Limit (MB)')
                    ->numeric()->default(500),
                Textarea::make('description')->rows(2),
            ])->columns(2),

            Section::make('Selected Mod Directories')
                ->schema([
                    CheckboxList::make('selectedDirectories')
                        ->relationship('selectedDirectories', 'name')
                        ->options(function () {
                            return FastDlDirectory::where('is_base', false)
                                ->where('is_active', true)
                                ->with('game')
                                ->get()
                                ->mapWithKeys(fn ($d) => [$d->id => $d->game->name . ' / ' . $d->name]);
                        })
                        ->helperText('Select which mod directories this clan gets access to'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('slug')->badge()->color('info'),
                TextColumn::make('game.name')->sortable(),
                TextColumn::make('leader.name')->label('Leader'),
                TextColumn::make('admins_count')
                    ->counts('admins')->label('Admins'),
                TextColumn::make('selected_directories_count')
                    ->counts('selectedDirectories')->label('Mods'),
                TextColumn::make('own_files_count')
                    ->counts('ownFiles')->label('Own Files'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFastDlClans::route('/'),
            'create' => CreateFastDlClan::route('/create'),
            'edit' => EditFastDlClan::route('/{record}/edit'),
        ];
    }
}
