<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Community';

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can('view_users');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Account')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('password')->password()->required()->hiddenOn('edit'),
                    Forms\Components\Select::make('roles')->relationship('roles', 'name')->multiple()->preload(),
                    Forms\Components\Toggle::make('is_active')->default(true),
                    Forms\Components\Toggle::make('is_trusted_uploader')->label('Trusted Uploader'),
                    Forms\Components\Select::make('locale')
                        ->options(['en'=>'English','de'=>'Deutsch','fr'=>'Français','nl'=>'Nederlands','pl'=>'Polski','tr'=>'Türkçe'])
                        ->default('en'),
                    Forms\Components\TextInput::make('total_uploads')->numeric()->label('Total Uploads'),
                    Forms\Components\TextInput::make('total_downloads')->numeric()->label('Total Downloads'),
                ]),
            Forms\Components\Section::make('Öffentliches Profil')
                ->columns(2)
                ->schema([
                    Forms\Components\Textarea::make('bio')->columnSpanFull()->rows(3),
                    Forms\Components\TextInput::make('website')->url()->placeholder('https://'),
                    Forms\Components\TextInput::make('clan')->placeholder('z.B. |ETI|Clan'),
                    Forms\Components\TextInput::make('discord_username')->label('Discord Username'),
                    Forms\Components\TextInput::make('telegram_username')->label('Telegram Username')->placeholder('@username'),
                    Forms\Components\CheckboxList::make('favorite_games')
                        ->label('Lieblingsspiele')
                        ->options(['et'=>'Wolfenstein: ET','rtcw'=>'Return to Castle Wolfenstein','etl'=>'ET: Legacy'])
                        ->columns(3)
                        ->columnSpanFull(),
                ]),
            Forms\Components\Section::make('Statistiken & Aktivität')
                ->columns(2)
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('last_login_at')->label('Letzter Login')->disabled(),
                    Forms\Components\TextInput::make('last_activity_at')->label('Letzte Aktivität')->disabled(),
                    Forms\Components\TextInput::make('created_at')->label('Registriert am')->disabled(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('roles.name')->badge(),
                Tables\Columns\TextColumn::make('total_uploads')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('last_login_at')->dateTime('d.m.Y H:i'),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d.m.Y'),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
