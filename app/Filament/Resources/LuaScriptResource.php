<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\LuaScriptResource\Pages\ListLuaScripts;
use App\Filament\Resources\LuaScriptResource\Pages\CreateLuaScript;
use App\Filament\Resources\LuaScriptResource\Pages\EditLuaScript;
use App\Filament\Resources\LuaScriptResource\Pages;
use App\Models\LuaScript;
use App\Models\Category;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LuaScriptResource extends Resource
{
    protected static ?string $model = LuaScript::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-code-bracket';
    protected static string | \UnitEnum | null $navigationGroup = 'Files';
    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }



    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(255),
            RichEditor::make('description')->columnSpanFull(),
            Select::make('category_id')
                ->label('Category')
                ->options(Category::where('type', 'lua')->pluck('name', 'id'))
                ->searchable(),
            FileUpload::make('file_path')
                ->label('LUA File')
                ->disk('s3')
                ->directory('lua-scripts')->visibility('public')
                ->acceptedFileTypes(['.lua', 'text/x-lua', 'application/octet-stream'])
                ->required(),
            Grid::make(3)->schema([
                TextInput::make('version')->maxLength(50),
                TextInput::make('min_lua_version')->maxLength(20),
                Select::make('status')->options([
                    'pending' => 'Pending', 'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ])->default('pending'),
            ]),
            CheckboxList::make('compatible_mods')
                ->options([
                    'etpub' => 'ETPub', 'silent' => 'Silent Mod',
                    'nitmod' => 'N!tmod', 'legacy' => 'ET: Legacy',
                    'jaymod' => 'Jaymod', 'etjump' => 'ETJump',
                ]),
            RichEditor::make('installation_guide')
                ->label('Installation Guide')
                ->columnSpanFull(),
            Toggle::make('is_featured'),
            Textarea::make('rejection_reason')->visible(fn (Get $get) => $get('status') === 'rejected'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('category.name'),
                TextColumn::make('version'),
                TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'approved' => 'success', 'pending' => 'warning', 'rejected' => 'danger', default => 'gray',
                }),
                TextColumn::make('download_count')->label('Downloads')->sortable(),
                TextColumn::make('user.name')->label('Author'),
                TextColumn::make('created_at')->dateTime('d.m.Y'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLuaScripts::route('/'),
            'create' => CreateLuaScript::route('/create'),
            'edit' => EditLuaScript::route('/{record}/edit'),
        ];
    }
}
