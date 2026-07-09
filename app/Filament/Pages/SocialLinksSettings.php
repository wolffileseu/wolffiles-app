<?php

namespace App\Filament\Pages;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\ColorPicker;
use App\Models\Setting;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

/**
 * @property \Filament\Schemas\Schema $form
 */
class SocialLinksSettings extends Page
{
    use HasPageShield;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-share';
    protected static string | \UnitEnum | null $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Social Links';
    protected static ?string $title = 'Social Links';
    protected static ?int $navigationSort = 15;
    protected string $view = 'filament.pages.social-links-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $links = Setting::get('social_links', []);
        $isActive = Setting::get('social_links_active', true);
        $position = Setting::get('social_links_position', 'left');

        $this->form->fill([
            'is_active' => $isActive,
            'position' => $position,
            'links' => $links,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Display Settings')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Show Social Links')
                            ->default(true),
                        Select::make('position')
                            ->label('Position')
                            ->options([
                                'left' => 'Left Side',
                                'right' => 'Right Side',
                            ])
                            ->default('left'),
                    ])->columns(2),

                Section::make('Social Links')
                    ->schema([
                        Repeater::make('links')
                            ->label('')
                            ->schema([
                                Select::make('platform')
                                    ->label('Platform')
                                    ->options([
                                        'discord' => 'Discord',
                                        'reddit' => 'Reddit',
                                        'facebook' => 'Facebook',
                                        'twitter' => 'Twitter / X',
                                        'youtube' => 'YouTube',
                                        'twitch' => 'Twitch',
                                        'github' => 'GitHub',
                                        'steam' => 'Steam',
                                        'instagram' => 'Instagram',
                                        'tiktok' => 'TikTok',
                                        'mastodon' => 'Mastodon',
                                        'bluesky' => 'Bluesky',
                                        'custom' => 'Custom',
                                    ])
                                    ->required()
                                    ->live(),
                                TextInput::make('url')
                                    ->label('URL')
                                    ->url()
                                    ->required()
                                    ->placeholder('https://discord.gg/...'),
                                TextInput::make('label')
                                    ->label('Tooltip Label')
                                    ->placeholder('Join our Discord')
                                    ->maxLength(50),
                                TextInput::make('custom_icon_svg')
                                    ->label('Custom SVG Icon (only for Custom platform)')
                                    ->placeholder('<svg>...</svg>')
                                    ->visible(fn (Get $get) => $get('platform') === 'custom'),
                                ColorPicker::make('color')
                                    ->label('Custom Color (optional)')
                                    ->placeholder('Uses platform default'),
                            ])
                            ->columns(2)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => ucfirst($state['platform'] ?? 'New Link') . ' — ' . ($state['label'] ?? $state['url'] ?? ''))
                            ->defaultItems(0)
                            ->addActionLabel('+ Add Social Link'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('social_links', $data['links'] ?? [], 'json', 'social');
        Setting::set('social_links_active', $data['is_active'] ?? true, 'boolean', 'social');
        Setting::set('social_links_position', $data['position'] ?? 'left', 'string', 'social');

        Notification::make()
            ->title('Social Links saved!')
            ->success()
            ->send();
    }
}
