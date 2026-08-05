<?php

namespace App\Filament\Resources\Support;

use App\Filament\Resources\Support\CategoryResource\Pages\CreateCategory;
use App\Filament\Resources\Support\CategoryResource\Pages\EditCategory;
use App\Filament\Resources\Support\CategoryResource\Pages\ListCategories;
use App\Models\Support\Category;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string | \UnitEnum | null $navigationGroup = 'Support';
    protected static ?string $navigationLabel = 'Categories';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Category')->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(120),
                TextInput::make('slug')->required()->maxLength(120)
                    ->helperText('Used in URLs and permission names'),
                Textarea::make('description')->rows(3)->columnSpanFull(),
                TextInput::make('icon')->maxLength(60)->placeholder('heroicon-o-server'),
                ColorPicker::make('color'),
                TextInput::make('sort_order')->numeric()->default(0),
                Toggle::make('is_active')->default(true),
            ]),

            Section::make('Access')->columns(2)->schema([
                TextInput::make('required_permission')->maxLength(120)
                    ->helperText('Optional. Staff needs this permission to see tickets here.'),
                Select::make('default_assignee_id')->label('Default assignee')
                    ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                    ->searchable()->nullable(),
                Toggle::make('allow_guests')->default(true)
                    ->helperText('Allow tickets without a Wolffiles account'),
            ]),

            Section::make('Discord')->columns(2)->collapsed()->schema([
                TextInput::make('discord_channel_id')->maxLength(32)
                    ->helperText('Parent channel for private ticket threads'),
                TextInput::make('discord_role_id')->maxLength(32)
                    ->helperText('Role mentioned on new tickets in this category'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->searchable()->weight('medium'),
                TextColumn::make('slug')->color('gray')->size('sm'),
                TextColumn::make('tickets_count')->counts('tickets')->label('Tickets')->badge(),
                IconColumn::make('allow_guests')->boolean()->label('Guests'),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('discord_channel_id')->label('Discord')->placeholder('—')
                    ->toggleable(),
                TextColumn::make('sort_order')->label('Sort')->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit'   => EditCategory::route('/{record}/edit'),
        ];
    }
}
