<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NdaResource\Pages\ListNdas;
use App\Filament\Resources\NdaResource\Pages\ViewNda;
use App\Models\Nda;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NdaResource extends Resource
{
    protected static ?string $model = Nda::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';

    protected static string | \UnitEnum | null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'NDA Vertraege';

    protected static ?string $modelLabel = 'NDA Vertrag';

    protected static ?string $pluralModelLabel = 'NDA Vertraege';

    protected static ?int $navigationSort = 32;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('volunteer_name')->label('Name'),
            TextInput::make('volunteer_email')->label('E-Mail'),
            TextInput::make('volunteer_username')->label('Benutzername'),
            TextInput::make('volunteer_discord')->label('Discord'),
            DatePicker::make('volunteer_birthdate')->label('Geburtsdatum'),
            TextInput::make('volunteer_country')->label('Land'),

            TextInput::make('role_name')->label('Rolle laut Vertrag'),
            TextInput::make('penalty_amount')->label('Vertragsstrafe (EUR)'),
            TextInput::make('log_retention_months')->label('Protokoll-Aufbewahrung (Monate)'),
            TextInput::make('locale')->label('Sprache'),
            TextInput::make('template_version')->label('Vorlagenversion'),

            TextInput::make('signed_at')->label('Unterschrieben am'),
            TextInput::make('signed_ip')->label('IP-Adresse'),
            TextInput::make('document_hash')->label('SHA-256 des Dokuments'),
            Textarea::make('signed_user_agent')->label('Browser')->rows(2)->columnSpanFull(),

            Textarea::make('rendered_body')
                ->label('Vertragstext zum Zeitpunkt der Unterschrift')
                ->rows(40)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('volunteer_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('volunteer_discord')
                    ->label('Discord')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('role_name')
                    ->label('Rolle')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('locale')
                    ->label('Sprache')
                    ->badge(),

                TextColumn::make('signed_at')
                    ->label('Unterschrieben')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('revoked_at')
                    ->label('Widerrufen')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('-')
                    ->color('danger'),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                ViewAction::make(),

                Action::make('verify')
                    ->label('Integritaet pruefen')
                    ->icon('heroicon-o-finger-print')
                    ->color('gray')
                    ->action(function (Nda $record): void {
                        if ($record->verifyIntegrity()) {
                            Notification::make()
                                ->title('Snapshot unveraendert')
                                ->body('Der gespeicherte Text entspricht exakt dem Hash von der Unterschrift.')
                                ->success()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('WARNUNG: Snapshot weicht ab')
                            ->body('Der gespeicherte Text stimmt nicht mehr mit dem Hash ueberein.')
                            ->danger()
                            ->persistent()
                            ->send();
                    }),

                Action::make('link_user')
                    ->label('Benutzer verknuepfen')
                    ->icon('heroicon-o-link')
                    ->color('primary')
                    ->modalDescription('Verknuepft den Vertrag mit einem Konto. Erst dadurch werden Rollen und Berechtigungen vergeben.')
                    ->schema([
                        Select::make('user_id')
                            ->label('Benutzerkonto')
                            ->options(fn () => User::query()
                                ->orderBy('name')
                                ->limit(500)
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search) => User::query()
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->limit(50)
                                ->pluck('name', 'id'))
                            ->required(),

                        Toggle::make('assign')
                            ->label('Rollen und Berechtigungen jetzt vergeben')
                            ->default(true),
                    ])
                    ->visible(fn (Nda $record): bool => $record->user_id === null && $record->revoked_at === null)
                    ->action(function (Nda $record, array $data): void {
                        $user = User::find($data['user_id']);

                        if ($user === null) {
                            Notification::make()->title('Benutzer nicht gefunden')->danger()->send();

                            return;
                        }

                        $record->update(['user_id' => $user->id]);

                        $failed = [];

                        if (! empty($data['assign'])) {
                            foreach ((array) $record->role_names as $role) {
                                try {
                                    $user->assignRole($role);
                                } catch (\Throwable $e) {
                                    $failed[] = 'Rolle ' . $role;
                                }
                            }

                            foreach ((array) $record->permissions as $permission) {
                                try {
                                    $user->givePermissionTo($permission);
                                } catch (\Throwable $e) {
                                    $failed[] = $permission;
                                }
                            }
                        }

                        if ($failed !== []) {
                            Notification::make()
                                ->title('Teilweise vergeben')
                                ->body('Nicht zuweisbar: ' . implode(', ', $failed))
                                ->warning()
                                ->persistent()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Verknuepft mit ' . $user->name)
                            ->success()
                            ->send();
                    }),

                Action::make('revoke')
                    ->label('Widerrufen')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Beendet die Vereinbarung. Der Vertrag bleibt als Nachweis erhalten.')
                    ->schema([
                        Textarea::make('revoked_reason')
                            ->label('Grund')
                            ->required()
                            ->rows(3),
                    ])
                    ->visible(fn (Nda $record): bool => $record->revoked_at === null)
                    ->action(function (Nda $record, array $data): void {
                        $record->update([
                            'revoked_at' => now(),
                            'revoked_reason' => $data['revoked_reason'],
                        ]);

                        Notification::make()
                            ->title('Vereinbarung widerrufen')
                            ->body('Denk daran, die Berechtigungen zu entziehen.')
                            ->warning()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNdas::route('/'),
            'view' => ViewNda::route('/{record}'),
        ];
    }
}
