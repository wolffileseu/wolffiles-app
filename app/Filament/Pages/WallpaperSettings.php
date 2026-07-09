<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

/**
 * @property \Filament\Forms\Form $form
 */
class WallpaperSettings extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Wallpaper Settings';
    protected static ?string $title = 'Wallpaper Settings';
    protected static ?int $navigationSort = 61;
    protected static string $view = 'filament.pages.wallpaper-settings';

    public ?array $data = [];


    public function mount(): void
    {
        $this->form->fill([
            'wallpaper_slideshow_enabled' => (bool) Setting::get('wallpaper_slideshow_enabled', false),
            'wallpaper_slideshow_interval' => (int) Setting::get('wallpaper_slideshow_interval', 10),
            'wallpaper_random_per_pageload' => (bool) Setting::get('wallpaper_random_per_pageload', true),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Slideshow Behavior')
                    ->description('Controls how multiple active wallpapers are displayed.')
                    ->schema([
                        Forms\Components\Toggle::make('wallpaper_slideshow_enabled')
                            ->label('Enable auto-rotation slideshow')
                            ->helperText('When ON: wallpapers fade between each other in the browser. When OFF: a single wallpaper is shown per page load.')
                            ->live(),
                        Forms\Components\TextInput::make('wallpaper_slideshow_interval')
                            ->label('Slideshow interval (seconds)')
                            ->numeric()
                            ->minValue(3)
                            ->maxValue(120)
                            ->default(10)
                            ->visible(fn (Forms\Get $get) => (bool) $get('wallpaper_slideshow_enabled')),
                        Forms\Components\Toggle::make('wallpaper_random_per_pageload')
                            ->label('Random pick per page load')
                            ->helperText('When slideshow is OFF and multiple wallpapers are active: pick a random one each visit.')
                            ->visible(fn (Forms\Get $get) => ! (bool) $get('wallpaper_slideshow_enabled')),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('wallpaper_slideshow_enabled', $data['wallpaper_slideshow_enabled'] ? '1' : '', 'boolean', 'wallpaper');
        Setting::set('wallpaper_slideshow_interval', (int) ($data['wallpaper_slideshow_interval'] ?? 10), 'integer', 'wallpaper');
        Setting::set('wallpaper_random_per_pageload', ($data['wallpaper_random_per_pageload'] ?? true) ? '1' : '', 'boolean', 'wallpaper');

        Notification::make()
            ->title('Wallpaper settings saved')
            ->success()
            ->send();
    }
}
