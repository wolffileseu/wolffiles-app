<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\EtuiModResource\Pages;
use App\Models\Etui\EtuiMod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EtuiModResource extends Resource
{
    protected static ?string $model = EtuiMod::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'ETUI Builder';

    protected static ?string $navigationLabel = 'Mods';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identity')->schema([
                Forms\Components\TextInput::make('slug')->required()->maxLength(64)
                    ->unique(ignoreRecord: true)
                    ->helperText('URL slug + ProfileRegistry key. lowercase / snake_case.'),
                Forms\Components\TextInput::make('name')->required()->maxLength(128),
                Forms\Components\Textarea::make('description')->rows(3),
                Forms\Components\Select::make('parser_profile')
                    ->required()
                    ->options(self::availableProfiles())
                    ->helperText('FQN of the ModProfile subclass. Drives parsing, includes, i18n.'),
            ]),
            Forms\Components\Section::make('Display')->schema([
                Forms\Components\TextInput::make('homepage_url')->url()->maxLength(255),
                Forms\Components\TextInput::make('icon_path')->maxLength(255)
                    ->helperText('Optional storage path — full S3 integration in Phase 3.'),
                Forms\Components\TextInput::make('order')->numeric()->default(0)
                    ->helperText('Sort order in the public mod list (asc).'),
                Forms\Components\Toggle::make('is_active')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('slug')->badge()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('parser_profile')
                    ->label('Profile')
                    ->formatStateUsing(fn (string $state): string => class_basename($state)),
                Tables\Columns\TextColumn::make('files_count')->counts('files')->label('Files'),
                Tables\Columns\TextColumn::make('projects_count')->counts('projects')->label('Projects'),
                Tables\Columns\TextColumn::make('order')->sortable(),
                Tables\Columns\ToggleColumn::make('is_active'),
            ])
            ->defaultSort('order')
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEtuiMods::route('/'),
            'create' => Pages\CreateEtuiMod::route('/create'),
            'edit' => Pages\EditEtuiMod::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>  FQN => short label
     */
    private static function availableProfiles(): array
    {
        $files = glob(app_path('Etui/Profiles/*Profile.php')) ?: [];
        $opts = [];
        foreach ($files as $file) {
            $base = basename($file, '.php');
            if ($base === 'ModProfile') {
                // abstract base — never selectable
                continue;
            }
            $fqn = 'App\\Etui\\Profiles\\'.$base;
            if (class_exists($fqn) && is_subclass_of($fqn, \App\Etui\Profiles\ModProfile::class)) {
                $opts[$fqn] = $base;
            }
        }
        ksort($opts);
        return $opts;
    }
}
