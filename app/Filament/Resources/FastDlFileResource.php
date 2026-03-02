<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FastDlFileResource\Pages;
use App\Models\FastDl\FastDlFile;
use App\Models\FastDl\FastDlDirectory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FastDlFileResource extends Resource
{
    protected static ?string $model = FastDlFile::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-down';
    protected static ?string $navigationGroup = 'Fast Download';
    protected static ?string $navigationLabel = 'Files';
    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can('view_fastdl_files');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('File')->schema([
                Forms\Components\Select::make('directory_id')
                    ->label('Directory')
                    ->options(
                        FastDlDirectory::with('game')->get()->mapWithKeys(fn ($d) => [$d->id => $d->game->name . ' / ' . $d->name])
                    )
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('filename')->required()
                    ->helperText('e.g. goldrush.pk3'),
                Forms\Components\FileUpload::make('upload')
                    ->label('Upload PK3')
                    ->disk('s3')
                    ->directory('fastdl')
                    
                    ->maxSize(102400)
                    ->helperText('Max 100MB. The file will be stored on S3.'),
                Forms\Components\TextInput::make('s3_path')
                    ->helperText('S3 path (auto-filled on upload, or enter manually)'),
                Forms\Components\Select::make('source')
                    ->options(['manual' => 'Manual Upload', 'auto_sync' => 'Auto-Sync', 'clan_upload' => 'Clan Upload'])
                    ->default('manual'),
                Forms\Components\Toggle::make('is_active')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('directory.game.name')->label('Game')->sortable(),
                Tables\Columns\TextColumn::make('directory.name')->label('Directory')->sortable(),
                Tables\Columns\TextColumn::make('filename')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('human_size')->label('Size'),
                Tables\Columns\TextColumn::make('source')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'auto_sync' => 'success',
                        'manual' => 'warning',
                        'clan_upload' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('download_count')->sortable()->label('DLs'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('filename')
            ->filters([
                Tables\Filters\SelectFilter::make('directory_id')
                    ->options(
                        FastDlDirectory::with('game')->get()->mapWithKeys(fn ($d) => [$d->id => $d->game->name . ' / ' . $d->name])
                    )
                    ->label('Directory'),
                Tables\Filters\SelectFilter::make('source')
                    ->options(['auto_sync' => 'Auto-Sync', 'manual' => 'Manual', 'clan_upload' => 'Clan']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFastDlFiles::route('/'),
            'create' => Pages\CreateFastDlFile::route('/create'),
            'edit' => Pages\EditFastDlFile::route('/{record}/edit'),
        ];
    }
}
