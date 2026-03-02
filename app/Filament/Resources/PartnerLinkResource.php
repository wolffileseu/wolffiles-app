<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartnerLinkResource\Pages;
use App\Models\PartnerLink;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PartnerLinkResource extends Resource
{
    protected static ?string $model = PartnerLink::class;
    protected static ?string $navigationIcon = 'heroicon-o-link';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Partner Links';
    protected static ?int $navigationSort = 50;


    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can('view_partner_links');
    }


    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\TextInput::make('url')->required()->url()->maxLength(255),
            Forms\Components\FileUpload::make('image')
                ->disk('s3')
                ->directory('partners')
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                ->maxSize(10240)
                ->imagePreviewHeight('80')
                ->helperText('Erlaubt: JPG, PNG, GIF, WebP (max. 10MB)'),
            Forms\Components\Select::make('group')
                ->options([
                    'clan' => 'Clan / Community',
                    'mod' => 'Mod / Project',
                    'other' => 'Other Links',
                ])
                ->default('clan')
                ->required(),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
            Forms\Components\Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')->disk('s3')->height(30),
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('url')->limit(40)->url(fn ($record) => $record->url, true),
                Tables\Columns\TextColumn::make('group')->badge()
                    ->color(fn ($state) => match ($state) {
                        'clan' => 'success',
                        'mod' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPartnerLinks::route('/'),
            'create' => Pages\CreatePartnerLink::route('/create'),
            'edit' => Pages\EditPartnerLink::route('/{record}/edit'),
        ];
    }
}
