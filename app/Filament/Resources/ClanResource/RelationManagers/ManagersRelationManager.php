<?php
namespace App\Filament\Resources\ClanResource\RelationManagers;

use App\Models\ClanManager;
use Filament\Forms;
use Filament\Forms\Form;
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

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label('User')
                ->relationship('user', 'name')
                ->searchable()
                ->required(),
            Forms\Components\Select::make('role')
                ->options([
                    'owner'  => 'Owner',
                    'admin'  => 'Admin',
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
                Tables\Columns\TextColumn::make('user.name')->label('User')->searchable(),
                Tables\Columns\TextColumn::make('user.email')->label('Email')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('role')->badge()->color(fn ($state) => match($state) {
                    'owner'  => 'warning',
                    'admin'  => 'success',
                    'editor' => 'info',
                    default  => 'gray',
                }),
                Tables\Columns\TextColumn::make('created_at')->since()->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('transfer')
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
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (ClanManager $record) => $record->role !== 'owner')
                    ->modalDescription('Cannot delete the owner. Transfer ownership first.'),
            ]);
    }
}
