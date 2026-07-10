<?php
namespace App\Filament\Resources\ClanResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use App\Models\ClanManager;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class ManagersRelationManager extends RelationManager
{
    protected static string $relationship = 'managers';
    protected static ?string $title = 'Managers';
    protected static ?string $recordTitleAttribute = 'role';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->label('User')
                ->relationship('user', 'name')
                ->searchable()
                ->required(),
            Select::make('role')
                ->options([
                    'leader' => 'Leader',
                    'owner'  => 'Owner',
                    'editor' => 'Editor',
                ])
                ->required()
                ->helperText('Note: setting role=owner here does NOT auto-demote the current owner. Use the Transfer action instead.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('User')->searchable(),
                TextColumn::make('user.email')->label('Email')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('role')->badge()->color(fn ($state) => match($state) {
                    'leader' => 'warning',
                    'owner'  => 'success',
                    'editor' => 'info',
                    default  => 'gray',
                }),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('transfer')
                    ->label('Transfer Ownership')
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('This will make this manager the new Owner, and demote the current owner to Editor.')
                    ->visible(fn (ClanManager $record) => $record->role !== 'owner')
                    ->action(function (ClanManager $record) {
                        DB::transaction(function () use ($record) {
                            $currentOwner = $record->clan->managers()->where('role', 'owner')->first();
                            if ($currentOwner) {
                                $currentOwner->update(['role' => 'editor']);
                            }
                            $record->update(['role' => 'owner']);
                        });
                        Notification::make()->title('Ownership transferred')
                            ->body($record->user->name . ' is now the Owner.')
                            ->success()->send();
                    }),
                DeleteAction::make()
                    ->visible(fn (ClanManager $record) => $record->role !== 'owner')
                    ->modalDescription('Cannot delete the owner. Transfer ownership first.'),
            ]);
    }
}
