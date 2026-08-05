<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NdaInvitationResource\Pages\CreateNdaInvitation;
use App\Filament\Resources\NdaInvitationResource\Pages\ListNdaInvitations;
use App\Models\NdaInvitation;
use App\Models\NdaTemplate;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class NdaInvitationResource extends Resource
{
    protected static ?string $model = NdaInvitation::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-paper-airplane';

    protected static string | \UnitEnum | null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'NDA Einladungen';

    protected static ?string $modelLabel = 'NDA Einladung';

    protected static ?string $pluralModelLabel = 'NDA Einladungen';

    protected static ?int $navigationSort = 31;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('locale')
                ->label('Sprache des Vertrags')
                ->options([
                    'de' => 'Deutsch',
                    'en' => 'English',
                ])
                ->default('de')
                ->required()
                ->live()
                ->afterStateUpdated(function ($state, callable $set) {
                    $template = NdaTemplate::activeFor((string) $state);
                    $set('nda_template_id', $template?->id);
                }),

            Select::make('nda_template_id')
                ->label('Vorlage')
                ->options(fn () => NdaTemplate::query()
                    ->orderBy('locale')
                    ->orderByDesc('version')
                    ->get()
                    ->mapWithKeys(fn (NdaTemplate $t) => [
                        $t->id => $t->name . ' (' . $t->locale . ' v' . $t->version . ')' . ($t->is_active ? ' - aktiv' : ''),
                    ]))
                ->required()
                ->searchable()
                ->helperText('Leer lassen ist nicht moeglich - die Version wird im Vertrag festgehalten.'),

            TextInput::make('recipient_label')
                ->label('Empfaenger (interne Notiz)')
                ->maxLength(255)
                ->helperText('Nur fuer deine Uebersicht, z.B. Nickname oder Clan.'),

            TextInput::make('recipient_email')
                ->label('E-Mail des Empfaengers')
                ->email()
                ->maxLength(255),

            TextInput::make('role_name')
                ->label('Rollenbezeichnung im Vertrag')
                ->required()
                ->maxLength(255)
                ->placeholder('Moderator (Discord und Gameserver)')
                ->helperText('Erscheint woertlich in Paragraph 1 des Vertrags.'),

            Select::make('role_names')
                ->label('Rollen (Spatie)')
                ->multiple()
                ->options(fn () => Role::orderBy('name')->pluck('name', 'name'))
                ->searchable()
                ->helperText('Werden erst NACH der Unterschrift vergeben.'),

            Select::make('permissions')
                ->label('Einzelberechtigungen')
                ->multiple()
                ->options(fn () => Permission::orderBy('name')->pluck('name', 'name'))
                ->searchable()
                ->optionsLimit(50)
                ->helperText('Optional zusaetzlich zu den Rollen. Werden im Vertrag aufgelistet.'),

            TextInput::make('penalty_amount')
                ->label('Vertragsstrafe (EUR)')
                ->numeric()
                ->default(500)
                ->minValue(0)
                ->required(),

            TextInput::make('log_retention_months')
                ->label('Aufbewahrung der Protokolle (Monate)')
                ->numeric()
                ->default(12)
                ->minValue(1)
                ->maxValue(120)
                ->required(),

            Select::make('authoritative_language')
                ->label('Massgebliche Sprachfassung')
                ->options([
                    'deutsche' => 'Deutsch',
                    'English' => 'English',
                ])
                ->default('deutsche')
                ->required(),

            DateTimePicker::make('expires_at')
                ->label('Link laeuft ab am')
                ->helperText('Leer lassen: Link bleibt gueltig, bis das Formular abgeschickt wird.'),

            Textarea::make('note')
                ->label('Interne Notiz')
                ->rows(3)
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

                TextColumn::make('recipient_label')
                    ->label('Empfaenger')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('role_name')
                    ->label('Rolle')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('locale')
                    ->label('Sprache')
                    ->badge(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        NdaInvitation::STATUS_SIGNED => 'success',
                        NdaInvitation::STATUS_PENDING => 'warning',
                        NdaInvitation::STATUS_EXPIRED => 'gray',
                        NdaInvitation::STATUS_REVOKED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        NdaInvitation::STATUS_SIGNED => 'unterschrieben',
                        NdaInvitation::STATUS_PENDING => 'offen',
                        NdaInvitation::STATUS_EXPIRED => 'abgelaufen',
                        NdaInvitation::STATUS_REVOKED => 'widerrufen',
                        default => $state,
                    }),

                TextColumn::make('expires_at')
                    ->label('Laeuft ab')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('unbegrenzt'),

                TextColumn::make('used_at')
                    ->label('Unterschrieben')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                Action::make('regenerate')
                    ->label('Neuer Link')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('Der bisherige Link wird sofort ungueltig. Ein neuer Link wird erzeugt und einmalig angezeigt.')
                    ->visible(fn (NdaInvitation $record): bool => $record->used_at === null)
                    ->action(function (NdaInvitation $record): void {
                        $token = NdaInvitation::generateToken();

                        $record->update([
                            'token_hash' => NdaInvitation::hashToken($token),
                            'revoked_at' => null,
                        ]);

                        static::notifyLink($token);
                    }),

                Action::make('revoke')
                    ->label('Widerrufen')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (NdaInvitation $record): bool => $record->used_at === null && $record->revoked_at === null)
                    ->action(function (NdaInvitation $record): void {
                        $record->update(['revoked_at' => now()]);

                        Notification::make()
                            ->title('Einladung widerrufen')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function notifyLink(string $token): void
    {
        $url = url('/nda/' . $token);

        Notification::make()
            ->title('Einladungslink erzeugt')
            ->body(new HtmlString(
                '<p style="margin-bottom:.5rem">Dieser Link wird <strong>nur jetzt</strong> angezeigt. '
                . 'Danach ist er nicht mehr abrufbar - dann musst du einen neuen erzeugen.</p>'
                . '<code style="word-break:break-all;font-size:.75rem">' . e($url) . '</code>'
            ))
            ->success()
            ->persistent()
            ->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNdaInvitations::route('/'),
            'create' => CreateNdaInvitation::route('/create'),
        ];
    }
}
