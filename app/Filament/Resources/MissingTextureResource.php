<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MissingTextureResource\Pages;
use App\Models\MissingTexture;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Form;
use App\Services\TextureResolutionChecker;
use Filament\Notifications\Notification;

class MissingTextureResource extends Resource
{
    protected static ?string $model = MissingTexture::class;
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationGroup = 'BSP Viewer';
    protected static ?string $navigationLabel = 'Missing Textures';
    protected static ?string $modelLabel = 'Missing Texture';
    protected static ?int $navigationSort = 90;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('resolved', false)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('texture_path')->disabled(),
                Forms\Components\TextInput::make('game')->disabled(),
                Forms\Components\TextInput::make('request_count')->disabled(),
                Forms\Components\DateTimePicker::make('first_seen_at')->disabled(),
                Forms\Components\DateTimePicker::make('last_seen_at')->disabled(),
                Forms\Components\Toggle::make('resolved')->label('Mark as resolved'),
                Forms\Components\Textarea::make('notes')->rows(3),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('request_count', 'desc')
            ->columns([
                Tables\Columns\IconColumn::make('resolved')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('file.map_name')
                    ->label('Map')
                    ->searchable()
                    ->url(fn($record) => $record->file ? '/files/' . $record->file->slug : null)
                    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('file.id')
                    ->label('File ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('texture_path')
                    ->label('Texture')
                    ->searchable()
                    ->limit(60)
                    ->tooltip(fn($record) => $record->texture_path)
                    ->copyable(),
                Tables\Columns\BadgeColumn::make('game')
                    ->colors([
                        'primary' => 'ET',
                        'warning' => 'RtCW',
                        'gray'    => fn($state) => !in_array($state, ['ET', 'RtCW']),
                    ]),
                Tables\Columns\TextColumn::make('request_count')
                    ->label('Hits')
                    ->sortable()
                    ->badge()
                    ->color(fn($state) => $state > 100 ? 'danger' : ($state > 10 ? 'warning' : 'gray')),
                Tables\Columns\TextColumn::make('last_seen_at')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('resolved')
                    ->label('Resolved')
                    ->default(false),
                Tables\Filters\SelectFilter::make('game')
                    ->options([
                        'ET'   => 'ET',
                        'RtCW' => 'RtCW',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('toggleResolved')
                    ->label(fn($record) => $record->resolved ? 'Unresolve' : 'Resolve')
                    ->icon(fn($record) => $record->resolved ? 'heroicon-o-arrow-uturn-left' : 'heroicon-o-check')
                    ->action(fn($record) => $record->update(['resolved' => !$record->resolved])),
                Tables\Actions\Action::make('recheck')
                    ->label('Re-check')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function ($record) {
                        $checker = app(TextureResolutionChecker::class);
                        if ($checker->recheckMissingTexture($record)) {
                            Notification::make()->title('Resolved!')->success()->send();
                        } else {
                            Notification::make()->title('Still missing')->warning()->send();
                        }
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('markResolved')
                    ->label('Mark resolved')
                    ->icon('heroicon-o-check')
                    ->action(fn($records) => $records->each->update(['resolved' => true]))
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkAction::make('recheckBulk')
                    ->label('Re-check selected')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function ($records) {
                        $checker = app(TextureResolutionChecker::class);
                        $resolved = 0;
                        foreach ($records as $r) {
                            if ($checker->recheckMissingTexture($r)) $resolved++;
                        }
                        Notification::make()
                            ->title("Rechecked {$records->count()}, resolved {$resolved}")
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMissingTextures::route('/'),
            'edit'  => Pages\EditMissingTexture::route('/{record}/edit'),
        ];
    }
}
