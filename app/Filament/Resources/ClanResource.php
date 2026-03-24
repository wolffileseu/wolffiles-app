<?php
namespace App\Filament\Resources;

use App\Filament\Resources\ClanResource\Pages;
use App\Models\Clan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClanResource extends Resource
{
    protected static ?string $model = Clan::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Clans';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Clan Info')->schema([
                Forms\Components\TextInput::make('name')
                    ->required()->maxLength(255),
                Forms\Components\TextInput::make('tag')
                    ->required()->maxLength(10)
                    ->prefix('[')->suffix(']')
                    ->helperText('Kurzes Clan-Tag, z.B. TAS'),
                Forms\Components\TextInput::make('slug')
                    ->required()->maxLength(255),
                Forms\Components\Toggle::make('is_active')
                    ->default(true)->inline(false),
            ])->columns(2),

            Forms\Components\Section::make('Details')->schema([
                Forms\Components\Textarea::make('description')
                    ->rows(3)->columnSpanFull(),
                Forms\Components\TextInput::make('website')
                    ->url()->maxLength(255),
                Forms\Components\TextInput::make('contact_discord')
                    ->maxLength(255)->prefix('discord.gg/'),
                Forms\Components\TextInput::make('contact_email')
                    ->email()->maxLength(255),
            ])->columns(2),

            Forms\Components\Section::make('Logo')->schema([
                Forms\Components\FileUpload::make('logo')
                    ->disk('s3')->directory('clans/logos')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])->maxSize(10240)
                    ->imagePreviewHeight('100')
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->circular()->size(40),
                Tables\Columns\TextColumn::make('tag')
                    ->badge()->color('primary')->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()->sortable(),
                Tables\Columns\TextColumn::make('apiKeys_count')
                    ->counts('apiKeys')->label('API Keys'),
                Tables\Columns\TextColumn::make('posts_count')
                    ->counts('posts')->label('Posts'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d.m.Y')->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Aktiv'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListClans::route('/'),
            'create' => Pages\CreateClan::route('/create'),
            'edit'   => Pages\EditClan::route('/{record}/edit'),
        ];
    }
}
