<?php
namespace App\Filament\Resources;

use App\Filament\Resources\ClanApiKeyResource\Pages;
use App\Models\Clan;
use App\Models\ClanApiKey;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ClanApiKeyResource extends Resource
{
    protected static ?string $model = ClanApiKey::class;
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationGroup = 'Clans';
    protected static ?string $navigationLabel = 'API Keys';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\Select::make('clan_id')
                    ->label('Clan')
                    ->options(Clan::where('is_active', true)->pluck('name', 'id'))
                    ->searchable()->required(),
                Forms\Components\TextInput::make('label')
                    ->label('Bezeichnung')
                    ->helperText('z.B. "Haupt-Key" oder "Backup"')
                    ->maxLength(255),
                Forms\Components\TextInput::make('key')
                    ->label('API Key')
                    ->default(fn() => Str::random(48))
                    ->readOnly()
                    ->helperText('Wird automatisch generiert'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Aktiv')->default(true)->inline(false),
                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('Ablaufdatum')->nullable()
                    ->helperText('Leer lassen = kein Ablaufdatum'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('clan.tag')
                    ->badge()->color('primary')->searchable(),
                Tables\Columns\TextColumn::make('clan.name')
                    ->searchable()->sortable(),
                Tables\Columns\TextColumn::make('label')
                    ->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('key')
                    ->label('API Key')
                    ->formatStateUsing(fn($state) => substr($state, 0, 8) . '...' . substr($state, -4))
                    ->copyable()->copyMessage('Key kopiert!'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()->label('Aktiv'),
                Tables\Columns\TextColumn::make('last_used_at')
                    ->dateTime('d.m.Y H:i')->sortable()->label('Zuletzt genutzt')
                    ->placeholder('Nie'),
                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime('d.m.Y')->sortable()->label('Läuft ab')
                    ->placeholder('Nie'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Aktiv'),
                Tables\Filters\SelectFilter::make('clan_id')
                    ->label('Clan')
                    ->options(Clan::pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\Action::make('toggle')
                    ->label(fn($record) => $record->is_active ? 'Sperren' : 'Aktivieren')
                    ->icon(fn($record) => $record->is_active ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->color(fn($record) => $record->is_active ? 'danger' : 'success')
                    ->action(function ($record) {
                        $record->update(['is_active' => !$record->is_active]);
                        Notification::make()
                            ->title($record->is_active ? 'Key aktiviert' : 'Key gesperrt')
                            ->success()->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index'  => Pages\ListClanApiKeys::route('/'),
            'create' => Pages\CreateClanApiKey::route('/create'),
            'edit'   => Pages\EditClanApiKey::route('/{record}/edit'),
        ];
    }
}
