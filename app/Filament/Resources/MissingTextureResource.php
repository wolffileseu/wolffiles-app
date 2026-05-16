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
use App\Services\MissingTextureUploader;
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
                Tables\Actions\Action::make('uploadTexture')
                    ->label('Upload')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->form(fn ($record) => [
                        Forms\Components\Placeholder::make('info')
                            ->label('Path')
                            ->content($record->texture_path),
                        Forms\Components\Placeholder::make('game_info')
                            ->label('Game')
                            ->content($record->game),
                        Forms\Components\Radio::make('destination')
                            ->label('Upload destination')
                            ->options([
                                'pool' => 'Game pool (' . ($record->game === 'RtCW' ? 'rtcw-assets' : 'et-assets') . ') — helps ALL maps',
                                's3'   => 'This map only (S3 bsp/' . $record->file_id . '/assets)',
                            ])
                            ->default(fn () => app(MissingTextureUploader::class)->suggestDestination($record))
                            ->required(),
                        Forms\Components\FileUpload::make('file')
                            ->label('Texture file')
                            ->required()
                            ->disk('local')
                            ->directory('temp/texture-uploads')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/x-tga', 'application/octet-stream'])
                            ->maxSize(10240)
                            ->helperText('JPG, PNG, WebP, or TGA. Max 10 MB.'),
                    ])
                    ->action(function ($record, array $data) {
                        $uploader = app(MissingTextureUploader::class);

                        // Filament returns the relative path within the configured disk.
                        // Resolve via Storage facade to handle any disk configuration.
                        $relPath = $data['file'];
                        if (is_array($relPath)) {
                            $relPath = reset($relPath);
                        }

                        // Try common disks
                        $diskCandidates = ['local', 'public'];
                        $contents = null;
                        $foundOn = null;
                        foreach ($diskCandidates as $disk) {
                            if (\Illuminate\Support\Facades\Storage::disk($disk)->exists($relPath)) {
                                $contents = \Illuminate\Support\Facades\Storage::disk($disk)->get($relPath);
                                $foundOn = $disk;
                                break;
                            }
                        }

                        if ($contents === null) {
                            \Illuminate\Support\Facades\Log::warning('Texture upload: file not found in any disk', [
                                'relPath' => $relPath,
                                'data'    => $data,
                            ]);
                            Notification::make()
                                ->title('File not found')
                                ->body('Path: ' . $relPath)
                                ->danger()->send();
                            return;
                        }

                        // Write contents to a temp file so we can pass an UploadedFile instance
                        $tmpFile = tempnam(sys_get_temp_dir(), 'tex_');
                        file_put_contents($tmpFile, $contents);
                        $uploaded = new \Illuminate\Http\UploadedFile(
                            $tmpFile,
                            basename($relPath),
                            null,
                            null,
                            true
                        );

                        $result = $uploader->upload($record, $uploaded, $data['destination']);

                        @unlink($tmpFile);
                        \Illuminate\Support\Facades\Storage::disk($foundOn)->delete($relPath);

                        if (!$result['success']) {
                            Notification::make()->title('Upload failed')
                                ->body($result['error'])->danger()->send();
                            return;
                        }
                        $siblings = $uploader->resolveSiblings($record, $data['destination']);
                        $msg = 'Uploaded to ' . $data['destination'];
                        if ($siblings > 0) $msg .= ' (+' . $siblings . ' sibling misses auto-resolved)';
                        Notification::make()->title($msg)->success()->send();
                    }),
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
