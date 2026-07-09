<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\FastDlFileResource\Pages\ListFastDlFiles;
use App\Filament\Resources\FastDlFileResource\Pages\CreateFastDlFile;
use App\Filament\Resources\FastDlFileResource\Pages\EditFastDlFile;
use App\Filament\Resources\FastDlFileResource\Pages;
use App\Models\FastDl\FastDlFile;
use App\Models\FastDl\FastDlDirectory;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FastDlFileResource extends Resource
{
    protected static ?string $model = FastDlFile::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-arrow-down';
    protected static string | \UnitEnum | null $navigationGroup = 'Fast Download';
    protected static ?string $navigationLabel = 'Files';
    protected static ?int $navigationSort = 3;


    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('File')->schema([
                Select::make('directory_id')
                    ->label('Directory')
                    ->options(
                        FastDlDirectory::with('game')->get()->mapWithKeys(fn ($d) => [$d->id => $d->game->name . ' / ' . $d->name])
                    )
                    ->required()
                    ->searchable(),
                TextInput::make('filename')->required()
                    ->helperText('e.g. goldrush.pk3'),
                FileUpload::make('upload')
                    ->label('Upload PK3')
                    ->disk('s3')
                    ->directory('fastdl')->visibility('public')
                    ->preserveFilenames()
                    
                    ->maxSize(102400)
                    ->helperText('Max 100MB. The file will be stored on S3.'),
                TextInput::make('s3_path')
                    ->helperText('S3 path (auto-filled on upload, or enter manually)'),
                Select::make('source')
                    ->options(['manual' => 'Manual Upload', 'auto_sync' => 'Auto-Sync', 'clan_upload' => 'Clan Upload'])
                    ->default('manual'),
                Toggle::make('is_active')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('directory.game.name')->label('Game')->sortable(),
                TextColumn::make('directory.name')->label('Directory')->sortable(),
                TextColumn::make('filename')->sortable()->searchable(),
                TextColumn::make('human_size')->label('Size'),
                TextColumn::make('source')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'auto_sync' => 'success',
                        'manual' => 'warning',
                        'clan_upload' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('download_count')->sortable()->label('DLs'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('filename')
            ->filters([
                SelectFilter::make('directory_id')
                    ->options(
                        FastDlDirectory::with('game')->get()->mapWithKeys(fn ($d) => [$d->id => $d->game->name . ' / ' . $d->name])
                    )
                    ->label('Directory'),
                SelectFilter::make('source')
                    ->options(['auto_sync' => 'Auto-Sync', 'manual' => 'Manual', 'clan_upload' => 'Clan']),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFastDlFiles::route('/'),
            'create' => CreateFastDlFile::route('/create'),
            'edit' => EditFastDlFile::route('/{record}/edit'),
        ];
    }
}
