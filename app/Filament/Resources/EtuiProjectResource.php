<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\EtuiProjectResource\Pages;
use App\Models\Etui\EtuiProject;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EtuiProjectResource extends Resource
{
    protected static ?string $model = EtuiProject::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationGroup = 'ETUI Builder';

    protected static ?string $navigationLabel = 'User Projects';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        // Projects are user-managed; admin sees them read-only for moderation.
        // Filament still wants a form schema for the View modal — make every
        // field disabled so the Edit page can't be reached via the form path.
        return $form->schema([
            Forms\Components\TextInput::make('name')->disabled(),
            Forms\Components\Select::make('user_id')->relationship('user', 'name')->disabled(),
            Forms\Components\Select::make('mod_id')->relationship('mod', 'name')->disabled(),
            Forms\Components\TextInput::make('share_token')->disabled(),
            Forms\Components\Toggle::make('is_public')->disabled(),
            Forms\Components\Textarea::make('content')->disabled()->rows(20)->columnSpanFull()
                ->extraInputAttributes(['style' => 'font-family: ui-monospace, Menlo, monospace; font-size: 13px;']),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('mod.slug')->badge()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\ToggleColumn::make('is_public'),
                Tables\Columns\TextColumn::make('parse_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'clean' => 'success',
                        'errors' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('last_saved_at')->dateTime('Y-m-d H:i'),
                Tables\Columns\TextColumn::make('revisions_count')->counts('revisions')->label('Revs'),
            ])
            ->defaultSort('last_saved_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('mod_id')
                    ->label('Mod')
                    ->relationship('mod', 'name'),
                Tables\Filters\TernaryFilter::make('is_public'),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('toggle_visibility')
                    ->label(fn (EtuiProject $r) => $r->is_public ? 'Make private' : 'Make public')
                    ->icon(fn (EtuiProject $r) => $r->is_public ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->action(function (EtuiProject $record): void {
                        $record->is_public = ! $record->is_public;
                        $record->save();
                        Notification::make()
                            ->title($record->name.' is now '.($record->is_public ? 'public' : 'private'))
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('revoke_share_token')
                    ->label('Revoke share link')
                    ->icon('heroicon-o-link-slash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Generates a new share_token. The old share URL stops working immediately.')
                    ->action(function (EtuiProject $record): void {
                        $record->share_token = EtuiProject::freshShareToken();
                        $record->save();
                        Notification::make()
                            ->title('Share link revoked')
                            ->body('New token: '.$record->share_token)
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEtuiProjects::route('/'),
        ];
    }
}
