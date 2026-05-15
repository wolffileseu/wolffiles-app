<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\EtuiFileResource\Pages;
use App\Models\Etui\EtuiFile;
use App\Models\Etui\EtuiMod;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EtuiFileResource extends Resource
{
    protected static ?string $model = EtuiFile::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-code-bracket';

    protected static ?string $navigationGroup = 'ETUI Builder';

    protected static ?string $navigationLabel = 'Stock Files';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\Select::make('mod_id')
                    ->relationship('mod', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('name')->required()->maxLength(255)
                    ->helperText('e.g. main.menu'),
            ]),
            Forms\Components\Grid::make(3)->schema([
                Forms\Components\Select::make('source')
                    ->options(['stock' => 'Stock', 'user' => 'User'])
                    ->default('user')
                    ->required(),
                Forms\Components\Select::make('uploader_id')
                    ->label('Uploader')
                    ->relationship('uploader', 'name')
                    ->searchable()
                    ->nullable(),
                Forms\Components\Toggle::make('is_public')->default(true),
            ]),
            Forms\Components\Textarea::make('content')
                ->required()
                ->rows(24)
                ->extraInputAttributes(['style' => 'font-family: ui-monospace, Menlo, monospace; font-size: 13px;'])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('mod.slug')->badge()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'stock' ? 'info' : 'warning'),
                Tables\Columns\TextColumn::make('parse_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'clean' => 'success',
                        'errors' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('parse_error_count')->label('Errors')->alignRight(),
                Tables\Columns\TextColumn::make('uploader.name')->label('Uploader')->placeholder('—'),
                Tables\Columns\TextColumn::make('human_filesize')->label('Size')->alignRight(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('mod_id')
                    ->label('Mod')
                    ->relationship('mod', 'name'),
                Tables\Filters\SelectFilter::make('source')
                    ->options(['stock' => 'Stock', 'user' => 'User']),
                Tables\Filters\SelectFilter::make('parse_status')
                    ->options([
                        'pending' => 'Pending',
                        'clean' => 'Clean',
                        'errors' => 'Errors',
                        'unknown' => 'Unknown',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('reparse')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function (EtuiFile $record): void {
                        $record->reparse();
                        Notification::make()
                            ->title("Reparsed: {$record->name}")
                            ->body("status: {$record->parse_status} ({$record->parse_error_count} errors)")
                            ->color($record->parse_status === 'clean' ? 'success' : 'warning')
                            ->send();
                    }),
                Tables\Actions\Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn (EtuiFile $record) => response()->streamDownload(
                        fn () => print((string) $record->content),
                        $record->name,
                        ['Content-Type' => 'text/plain; charset=utf-8'],
                    )),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('reparse_all')
                        ->label('Reparse selected')
                        ->icon('heroicon-o-arrow-path')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $records->each->reparse();
                            Notification::make()
                                ->title("Reparsed {$records->count()} files")
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEtuiFiles::route('/'),
            'create' => Pages\CreateEtuiFile::route('/create'),
            'edit' => Pages\EditEtuiFile::route('/{record}/edit'),
        ];
    }
}
