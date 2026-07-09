<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\PartnerLinkResource\Pages\ListPartnerLinks;
use App\Filament\Resources\PartnerLinkResource\Pages\CreatePartnerLink;
use App\Filament\Resources\PartnerLinkResource\Pages\EditPartnerLink;
use App\Filament\Resources\PartnerLinkResource\Pages;
use App\Models\PartnerLink;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PartnerLinkResource extends Resource
{
    protected static ?string $model = PartnerLink::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-link';
    protected static string | \UnitEnum | null $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Partner Links';
    protected static ?int $navigationSort = 50;




    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('url')->required()->url()->maxLength(255),
            FileUpload::make('image')
                ->disk('s3')
                ->directory('partners')->visibility('public')
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                ->maxSize(10240)
                ->imagePreviewHeight('80')
                ->helperText('Erlaubt: JPG, PNG, GIF, WebP (max. 10MB)'),
            Select::make('group')
                ->options([
                    'clan' => 'Clan / Community',
                    'mod' => 'Mod / Project',
                    'other' => 'Other Links',
                ])
                ->default('clan')
                ->required(),
            TextInput::make('sort_order')->numeric()->default(0),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')->disk('s3')->height(30),
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('url')->limit(40)->url(fn ($record) => $record->url, true),
                TextColumn::make('group')->badge()
                    ->color(fn ($state) => match ($state) {
                        'clan' => 'success',
                        'mod' => 'info',
                        default => 'gray',
                    }),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPartnerLinks::route('/'),
            'create' => CreatePartnerLink::route('/create'),
            'edit' => EditPartnerLink::route('/{record}/edit'),
        ];
    }
}
