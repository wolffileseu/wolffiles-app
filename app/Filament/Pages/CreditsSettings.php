<?php

namespace App\Filament\Pages;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use App\Models\Setting;
use Filament\Forms;
use Filament\Pages\Page;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * @property \Filament\Schemas\Schema $form
 */
class CreditsSettings extends Page
{
    use HasPageShield;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-heart';
    protected static string | \UnitEnum | null $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Credits';
    protected static ?string $title = 'Credits verwalten';
    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.pages.credits-settings';

    public ?array $data = [];


    public function mount(): void
    {
        $credits = Setting::get('credits_entries', []);
        $headerText = Setting::get('credits_header_text', 'Wolffiles.eu wird möglich gemacht durch diese großartigen Menschen und Projekte.');
        $footerText = Setting::get('credits_footer_text', 'Vielen Dank an alle, die Wolffiles.eu unterstützen! ❤️');
        $isActive = Setting::get('credits_page_active', true);

        $this->form->fill([
            'header_text' => $headerText,
            'footer_text' => $footerText,
            'is_active' => $isActive,
            'entries' => is_array($credits) ? $credits : [],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Credits Seite')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Credits Seite aktiv')
                            ->default(true),
                        Textarea::make('header_text')
                            ->label('Einleitungstext')
                            ->rows(3),
                        Textarea::make('footer_text')
                            ->label('Abschlusstext')
                            ->rows(2),
                    ]),

                Section::make('Credits Einträge')
                    ->description('Personen, Teams und Projekte die du danken möchtest.')
                    ->schema([
                        Repeater::make('entries')
                            ->label('')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(100)
                                    ->columnSpan(1),
                                TextInput::make('role')
                                    ->label('Rolle / Beitrag')
                                    ->maxLength(150)
                                    ->placeholder('z.B. Server Admin, Map Creator, Sponsor...')
                                    ->columnSpan(1),
                                Select::make('category')
                                    ->label('Kategorie')
                                    ->options([
                                        'team' => '👥 Team',
                                        'contributor' => '🛠️ Contributor',
                                        'donor' => '💰 Sponsor / Donor',
                                        'community' => '🌍 Community',
                                        'project' => '📦 Projekt / Tool',
                                        'special' => '⭐ Besonderer Dank',
                                    ])
                                    ->default('contributor')
                                    ->columnSpan(1),
                                TextInput::make('url')
                                    ->label('Link (optional)')
                                    ->url()
                                    ->maxLength(255)
                                    ->placeholder('https://...')
                                    ->columnSpan(1),
                                TextInput::make('avatar_url')
                                    ->label('Avatar URL (optional)')
                                    ->maxLength(255)
                                    ->placeholder('https://...avatar.png')
                                    ->columnSpan(1),
                                Textarea::make('description')
                                    ->label('Beschreibung (optional)')
                                    ->rows(2)
                                    ->maxLength(500)
                                    ->columnSpan(2),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => ($state['name'] ?? '') . ($state['role'] ? ' — ' . $state['role'] : ''))
                            ->addActionLabel('Person / Projekt hinzufügen')
                            ->reorderable()
                            ->defaultItems(0),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('credits_entries', $data['entries'] ?? [], 'json', 'credits');
        Setting::set('credits_header_text', $data['header_text'] ?? '', 'string', 'credits');
        Setting::set('credits_footer_text', $data['footer_text'] ?? '', 'string', 'credits');
        Setting::set('credits_page_active', $data['is_active'] ?? true, 'boolean', 'credits');

        Notification::make()
            ->title('Credits gespeichert!')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Vorschau')
                ->icon('heroicon-o-eye')
                ->url(route('credits'))
                ->openUrlInNewTab(),
        ];
    }
}
