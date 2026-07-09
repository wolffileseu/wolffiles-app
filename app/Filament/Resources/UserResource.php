<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\CheckboxList;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';
    protected static string | \UnitEnum | null $navigationGroup = 'Community';


    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Account')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required(),
                    TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                    TextInput::make('password')->password()->required()->hiddenOn('edit'),
                    Select::make('roles')->relationship('roles', 'name')->multiple()->preload(),
                    Toggle::make('is_active')->default(true),
                    Toggle::make('is_trusted_uploader')->label('Trusted Uploader'),
                    Select::make('locale')
                        ->options(['en'=>'English','de'=>'Deutsch','fr'=>'Français','nl'=>'Nederlands','pl'=>'Polski','tr'=>'Türkçe'])
                        ->default('en'),
                    TextInput::make('total_uploads')->numeric()->label('Total Uploads'),
                    TextInput::make('total_downloads')->numeric()->label('Total Downloads'),
                ]),
            Section::make('Öffentliches Profil')
                ->columns(2)
                ->schema([
                    Textarea::make('bio')->columnSpanFull()->rows(3),
                    TextInput::make('website')->url()->placeholder('https://'),
                    TextInput::make('clan')->placeholder('z.B. |ETI|Clan'),
                    TextInput::make('discord_username')->label('Discord Username'),
                    TextInput::make('telegram_username')->label('Telegram Username')->placeholder('@username'),
                    CheckboxList::make('favorite_games')
                        ->label('Lieblingsspiele')
                        ->options(['et'=>'Wolfenstein: ET','rtcw'=>'Return to Castle Wolfenstein','etl'=>'ET: Legacy'])
                        ->columns(3)
                        ->columnSpanFull(),
                ]),
            Section::make('Statistiken & Aktivität')
                ->columns(2)
                ->collapsed()
                ->schema([
                    TextInput::make('last_login_at')->label('Letzter Login')->disabled(),
                    TextInput::make('last_activity_at')->label('Letzte Aktivität')->disabled(),
                    TextInput::make('created_at')->label('Registriert am')->disabled(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('roles.name')->badge(),
                TextColumn::make('total_uploads')->sortable(),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('last_login_at')->dateTime('d.m.Y H:i'),
                TextColumn::make('created_at')->dateTime('d.m.Y'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
