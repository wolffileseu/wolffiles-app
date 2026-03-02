<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LuaScriptResource\Pages;
use App\Models\LuaScript;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LuaScriptResource extends Resource
{
    protected static ?string $model = LuaScript::class;
    protected static ?string $navigationIcon = 'heroicon-o-code-bracket';
    protected static ?string $navigationGroup = 'Files';
    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }


    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can('view_lua_scripts');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->required()->maxLength(255),
            Forms\Components\RichEditor::make('description')->columnSpanFull(),
            Forms\Components\Select::make('category_id')
                ->label('Category')
                ->options(Category::where('type', 'lua')->pluck('name', 'id'))
                ->searchable(),
            Forms\Components\FileUpload::make('file_path')
                ->label('LUA File')
                ->disk('s3')
                ->directory('lua-scripts')
                ->acceptedFileTypes(['.lua', 'text/x-lua', 'application/octet-stream'])
                ->required(),
            Forms\Components\Grid::make(3)->schema([
                Forms\Components\TextInput::make('version')->maxLength(50),
                Forms\Components\TextInput::make('min_lua_version')->maxLength(20),
                Forms\Components\Select::make('status')->options([
                    'pending' => 'Pending', 'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ])->default('pending'),
            ]),
            Forms\Components\CheckboxList::make('compatible_mods')
                ->options([
                    'etpub' => 'ETPub', 'silent' => 'Silent Mod',
                    'nitmod' => 'N!tmod', 'legacy' => 'ET: Legacy',
                    'jaymod' => 'Jaymod', 'etjump' => 'ETJump',
                ]),
            Forms\Components\RichEditor::make('installation_guide')
                ->label('Installation Guide')
                ->columnSpanFull(),
            Forms\Components\Toggle::make('is_featured'),
            Forms\Components\Textarea::make('rejection_reason')->visible(fn (Forms\Get $get) => $get('status') === 'rejected'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category.name'),
                Tables\Columns\TextColumn::make('version'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'approved' => 'success', 'pending' => 'warning', 'rejected' => 'danger', default => 'gray',
                }),
                Tables\Columns\TextColumn::make('download_count')->label('Downloads')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('Author'),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d.m.Y'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLuaScripts::route('/'),
            'create' => Pages\CreateLuaScript::route('/create'),
            'edit' => Pages\EditLuaScript::route('/{record}/edit'),
        ];
    }
}
