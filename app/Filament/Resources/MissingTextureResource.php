<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Filament\Actions\EditAction;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\MissingTextureResource\Pages\ListMissingTextures;
use App\Filament\Resources\MissingTextureResource\Pages\EditMissingTexture;
use App\Filament\Resources\MissingTextureResource\Pages;
use App\Models\MissingTexture;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Services\TextureResolutionChecker;
use App\Services\MissingTextureUploader;
use Filament\Notifications\Notification;

class MissingTextureResource extends Resource
{
    protected static ?string $model = MissingTexture::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static string | \UnitEnum | null $navigationGroup = 'BSP Viewer';
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

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextInput::make('texture_path')->disabled(),
                TextInput::make('game')->disabled(),
                TextInput::make('request_count')->disabled(),
                DateTimePicker::make('first_seen_at')->disabled(),
                DateTimePicker::make('last_seen_at')->disabled(),
                Toggle::make('resolved')->label('Mark as resolved'),
                Textarea::make('notes')->rows(3),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('request_count', 'desc')
            ->columns([
                IconColumn::make('resolved')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                TextColumn::make('file.map_name')
                    ->label('Map')
                    ->searchable()
                    ->url(fn($record) => $record->file ? '/files/' . $record->file->slug : null)
                    ->openUrlInNewTab(),
                TextColumn::make('file.id')
                    ->label('File ID')
                    ->sortable(),
                TextColumn::make('texture_path')
                    ->label('Texture')
                    ->searchable()
                    ->limit(60)
                    ->tooltip(fn($record) => $record->texture_path)
                    ->copyable(),
                BadgeColumn::make('game')
                    ->colors([
                        'primary' => 'ET',
                        'warning' => 'RtCW',
                        'gray'    => fn($state) => !in_array($state, ['ET', 'RtCW']),
                    ]),
                TextColumn::make('request_count')
                    ->label('Hits')
                    ->sortable()
                    ->badge()
                    ->color(fn($state) => $state > 100 ? 'danger' : ($state > 10 ? 'warning' : 'gray')),
                TextColumn::make('last_seen_at')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('resolved')
                    ->label('Resolved')
                    ->default(false),
                SelectFilter::make('game')
                    ->options([
                        'ET'   => 'ET',
                        'RtCW' => 'RtCW',
                    ]),
            ])
            ->recordActions([
                Action::make('toggleResolved')
                    ->label(fn($record) => $record->resolved ? 'Unresolve' : 'Resolve')
                    ->icon(fn($record) => $record->resolved ? 'heroicon-o-arrow-uturn-left' : 'heroicon-o-check')
                    ->action(fn($record) => $record->update(['resolved' => !$record->resolved])),
                Action::make('uploadTexture')
                    ->label('Upload')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->schema(fn ($record) => [
                        Placeholder::make('info')
                            ->label('Path')
                            ->content($record->texture_path),
                        Placeholder::make('game_info')
                            ->label('Game')
                            ->content($record->game),
                        Radio::make('destination')
                            ->label('Upload destination')
                            ->options([
                                'pool' => 'Game pool (' . ($record->game === 'RtCW' ? 'rtcw-assets' : 'et-assets') . ') — helps ALL maps',
                                's3'   => 'This map only (S3 bsp/' . $record->file_id . '/assets)',
                            ])
                            ->default(fn () => app(MissingTextureUploader::class)->suggestDestination($record))
                            ->required(),
                        FileUpload::make('file')
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
                            if (Storage::disk($disk)->exists($relPath)) {
                                $contents = Storage::disk($disk)->get($relPath);
                                $foundOn = $disk;
                                break;
                            }
                        }

                        if ($contents === null) {
                            Log::warning('Texture upload: file not found in any disk', [
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
                        $uploaded = new UploadedFile(
                            $tmpFile,
                            basename($relPath),
                            null,
                            null,
                            true
                        );

                        $result = $uploader->upload($record, $uploaded, $data['destination']);

                        @unlink($tmpFile);
                        Storage::disk($foundOn)->delete($relPath);

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
                Action::make('recheck')
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
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkAction::make('markResolved')
                    ->label('Mark resolved')
                    ->icon('heroicon-o-check')
                    ->action(fn($records) => $records->each->update(['resolved' => true]))
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('recheckBulk')
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
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMissingTextures::route('/'),
            'edit'  => EditMissingTexture::route('/{record}/edit'),
        ];
    }
}
