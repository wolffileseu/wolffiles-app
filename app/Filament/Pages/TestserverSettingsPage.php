<?php

namespace App\Filament\Pages;

use App\Models\TestserverSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class TestserverSettingsPage extends Page implements HasForms
{
    use HasPageShield;

    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string $view = 'filament.pages.testserver-settings-page';
    protected static ?string $navigationGroup = 'Server Hosting';
    protected static ?string $navigationLabel = 'Testserver Settings';
    protected static ?int $navigationSort = 12;
    protected static ?string $title = 'Testserver Settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = TestserverSetting::current();
        $this->form->fill($settings->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Section::make('🎮 Feature Toggles')
                    ->description('Hauptschalter für das Testserver-System')
                    ->columns(3)
                    ->schema([
                        Toggle::make('feature_enabled')
                            ->label('System aktiviert')
                            ->helperText('Kill-Switch: wenn aus, ist /testserver/launch komplett deaktiviert.')
                            ->onColor('success')
                            ->offColor('danger'),
                        Toggle::make('public_visible')
                            ->label('Sidebar-Button auf Map-Pages')
                            ->helperText('"Map testen"-Button anzeigen?'),
                        Toggle::make('require_login')
                            ->label('Login erforderlich')
                            ->helperText('Nur eingeloggte User dürfen testen (für später).'),
                    ]),

                Section::make('☁️ Cloudflare Turnstile (Captcha)')
                    ->description('Schutz vor Bots beim Reservieren. Vorbereitet, default deaktiviert.')
                    ->columns(1)
                    ->schema([
                        Toggle::make('turnstile_enabled')
                            ->label('Turnstile aktivieren')
                            ->live()
                            ->helperText('Bei aktiviert wird vor jedem Reservieren ein unsichtbares Captcha geprüft.'),
                        TextInput::make('turnstile_site_key')
                            ->label('Turnstile Site Key')
                            ->password()
                            ->revealable()
                            ->maxLength(128)
                            ->visible(fn (callable $get) => $get('turnstile_enabled')),
                        TextInput::make('turnstile_secret_key')
                            ->label('Turnstile Secret Key')
                            ->password()
                            ->revealable()
                            ->maxLength(128)
                            ->visible(fn (callable $get) => $get('turnstile_enabled')),
                    ])
                    ->collapsible()
                    ->collapsed(fn ($get) => !$get('turnstile_enabled')),

                Section::make('🛡️ Rate Limiting')
                    ->description('Begrenzungen pro IP. Vorbereitet, default deaktiviert (jeder darf unbegrenzt).')
                    ->columns(1)
                    ->schema([
                        Toggle::make('rate_limit_enabled')
                            ->label('Rate-Limiting aktivieren')
                            ->live()
                            ->helperText('Wenn aus: keine Limits, jede IP darf beliebig oft testen.'),

                        Section::make('Anonyme Nutzer (nicht eingeloggt)')
                            ->columns(2)
                            ->schema([
                                TextInput::make('anon_max_per_hour')
                                    ->label('Max pro Stunde')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(100)
                                    ->required(),
                                TextInput::make('anon_max_per_day')
                                    ->label('Max pro Tag')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(500)
                                    ->required(),
                            ])
                            ->visible(fn ($get) => $get('rate_limit_enabled'))
                            ->compact(),

                        Section::make('Eingeloggte Nutzer')
                            ->columns(2)
                            ->schema([
                                TextInput::make('user_max_per_hour')
                                    ->label('Max pro Stunde')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(100)
                                    ->required(),
                                TextInput::make('user_max_per_day')
                                    ->label('Max pro Tag')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(500)
                                    ->required(),
                            ])
                            ->visible(fn ($get) => $get('rate_limit_enabled'))
                            ->compact(),

                        TextInput::make('cooldown_minutes')
                            ->label('Cooldown zwischen Sessions')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(120)
                            ->suffix('Minuten')
                            ->required()
                            ->visible(fn ($get) => $get('rate_limit_enabled')),
                    ])
                    ->collapsible()
                    ->collapsed(fn ($get) => !$get('rate_limit_enabled')),

                Section::make('⏱️ Session-Dauer')
                    ->columns(1)
                    ->schema([
                        TextInput::make('default_session_minutes')
                            ->label('Standard Session-Dauer')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(120)
                            ->suffix('Minuten')
                            ->required()
                            ->helperText('Wird pro Server überschrieben durch das `max_session_minutes` Feld in Testservers.'),
                    ]),

                Section::make('📝 Public-Texte')
                    ->description('Texte auf der /testserver/launch Seite')
                    ->columns(1)
                    ->schema([
                        Textarea::make('public_intro_text')
                            ->label('Intro-Text (oben auf der Page)')
                            ->rows(3)
                            ->maxLength(500),
                        Textarea::make('public_rules_text')
                            ->label('Regeln/Hinweise')
                            ->rows(3)
                            ->maxLength(500),
                    ])
                    ->collapsible(),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $settings = TestserverSetting::current();
        $settings->update($data);

        Notification::make()
            ->title('Einstellungen gespeichert')
            ->success()
            ->send();
    }

    public function resetToDefaults(): void
    {
        $defaults = [
            'feature_enabled'         => true,
            'public_visible'          => true,
            'require_login'           => false,
            'turnstile_enabled'       => false,
            'rate_limit_enabled'      => false,
            'anon_max_per_hour'       => 2,
            'anon_max_per_day'        => 6,
            'user_max_per_hour'       => 3,
            'user_max_per_day'        => 10,
            'cooldown_minutes'        => 5,
            'default_session_minutes' => 20,
        ];

        $settings = TestserverSetting::current();
        $settings->update($defaults);
        $this->form->fill($settings->fresh()->toArray());

        Notification::make()
            ->title('Auf Standardwerte zurückgesetzt')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Speichern')
                ->color('primary')
                ->icon('heroicon-o-check')
                ->action('save'),
            Action::make('reset')
                ->label('Defaults wiederherstellen')
                ->color('gray')
                ->icon('heroicon-o-arrow-uturn-left')
                ->requiresConfirmation()
                ->modalHeading('Wirklich alle Einstellungen auf Defaults zurücksetzen?')
                ->action('resetToDefaults'),
        ];
    }
}
