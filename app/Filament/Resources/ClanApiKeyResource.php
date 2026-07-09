<?php
namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\ClanApiKeyResource\Pages\ListClanApiKeys;
use App\Filament\Resources\ClanApiKeyResource\Pages\CreateClanApiKey;
use App\Filament\Resources\ClanApiKeyResource\Pages\EditClanApiKey;
use App\Filament\Resources\ClanApiKeyResource\Pages;
use App\Models\Clan;
use App\Models\ClanApiKey;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ClanApiKeyResource extends Resource
{
    protected static ?string $model = ClanApiKey::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-key';
    protected static string | \UnitEnum | null $navigationGroup = 'Clans';
    protected static ?string $navigationLabel = 'API Keys';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                Select::make('clan_id')
                    ->label('Clan')
                    ->options(Clan::where('is_active', true)->pluck('name', 'id'))
                    ->searchable()->required(),
                TextInput::make('label')
                    ->label('Bezeichnung')
                    ->helperText('z.B. "Haupt-Key" oder "Backup"')
                    ->maxLength(255),
                TextInput::make('key')
                    ->label('API Key')
                    ->default(fn() => Str::random(48))
                    ->readOnly()
                    ->helperText('Wird automatisch generiert'),
                Toggle::make('is_active')
                    ->label('Aktiv')->default(true)->inline(false),
                DateTimePicker::make('expires_at')
                    ->label('Ablaufdatum')->nullable()
                    ->helperText('Leer lassen = kein Ablaufdatum'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('clan.tag')
                    ->badge()->color('primary')->searchable(),
                TextColumn::make('clan.name')
                    ->searchable()->sortable(),
                TextColumn::make('label')
                    ->searchable()->placeholder('—'),
                TextColumn::make('key')
                    ->label('API Key')
                    ->formatStateUsing(fn($state) => substr($state, 0, 8) . '...' . substr($state, -4))
                    ->copyable()->copyMessage('Key kopiert!'),
                IconColumn::make('is_active')
                    ->boolean()->label('Aktiv'),
                TextColumn::make('last_used_at')
                    ->dateTime('d.m.Y H:i')->sortable()->label('Zuletzt genutzt')
                    ->placeholder('Nie'),
                TextColumn::make('expires_at')
                    ->dateTime('d.m.Y')->sortable()->label('Läuft ab')
                    ->placeholder('Nie'),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Aktiv'),
                SelectFilter::make('clan_id')
                    ->label('Clan')
                    ->options(Clan::pluck('name', 'id')),
            ])
            ->recordActions([
                Action::make('toggle')
                    ->label(fn($record) => $record->is_active ? 'Sperren' : 'Aktivieren')
                    ->icon(fn($record) => $record->is_active ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->color(fn($record) => $record->is_active ? 'danger' : 'success')
                    ->action(function ($record) {
                        $record->update(['is_active' => !$record->is_active]);
                        Notification::make()
                            ->title($record->is_active ? 'Key aktiviert' : 'Key gesperrt')
                            ->success()->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
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
            'index'  => ListClanApiKeys::route('/'),
            'create' => CreateClanApiKey::route('/create'),
            'edit'   => EditClanApiKey::route('/{record}/edit'),
        ];
    }
}
