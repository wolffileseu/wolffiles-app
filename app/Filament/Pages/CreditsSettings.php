<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * @property \Filament\Forms\Form $form
 */
class CreditsSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-heart';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Credits';
    protected static ?string $title = 'Credits verwalten';
    protected static ?int $navigationSort = 90;

    protected static string $view = 'filament.pages.credits-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

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

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Credits Seite')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Credits Seite aktiv')
                            ->default(true),
                        Forms\Components\Textarea::make('header_text')
                            ->label('Einleitungstext')
                            ->rows(3),
                        Forms\Components\Textarea::make('footer_text')
                            ->label('Abschlusstext')
                            ->rows(2),
                    ]),

                Forms\Components\Section::make('Credits Einträge')
                    ->description('Personen, Teams und Projekte die du danken möchtest.')
                    ->schema([
                        Forms\Components\Repeater::make('entries')
                            ->label('')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(100)
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('role')
                                    ->label('Rolle / Beitrag')
                                    ->maxLength(150)
                                    ->placeholder('z.B. Server Admin, Map Creator, Sponsor...')
                                    ->columnSpan(1),
                                Forms\Components\Select::make('category')
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
                                Forms\Components\TextInput::make('url')
                                    ->label('Link (optional)')
                                    ->url()
                                    ->maxLength(255)
                                    ->placeholder('https://...')
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('avatar_url')
                                    ->label('Avatar URL (optional)')
                                    ->maxLength(255)
                                    ->placeholder('https://...avatar.png')
                                    ->columnSpan(1),
                                Forms\Components\Textarea::make('description')
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
