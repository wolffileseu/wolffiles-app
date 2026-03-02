<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\HtmlString;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Wolffiles Admin')
            ->brandLogo(asset('images/wolffiles_logo.png'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('images/favicon.ico'))
            ->colors([
                'primary' => Color::Amber,
                'danger' => Color::Red,
                'gray' => Color::Zinc,
                'info' => Color::Blue,
                'success' => Color::Green,
                'warning' => Color::Orange,
            ])
            ->navigationGroups([
                NavigationGroup::make('Content')->icon('heroicon-o-document-text'),
                NavigationGroup::make('Files')->icon('heroicon-o-folder'),
                NavigationGroup::make('Community')->icon('heroicon-o-user-group'),
                NavigationGroup::make('Tracker')->icon('heroicon-o-signal'),
                NavigationGroup::make('Donations')->icon('heroicon-o-heart'),
                NavigationGroup::make('Settings')->icon('heroicon-o-cog-6-tooth'),
                NavigationGroup::make('System')->icon('heroicon-o-cog-6-tooth'),
            ])
            ->navigationItems([
                NavigationItem::make('Back to Website')
                    ->url('/')
                    ->icon('heroicon-o-arrow-left')
                    ->sort(-99)
                    ->openUrlInNewTab(false),
            ])
            ->renderHook(
                'panels::head.end',
                fn () => new HtmlString('
                    <style>
                        /* Mobile sidebar scroll fix */
                        .fi-sidebar-nav,
                        .fi-sidebar,
                        aside.fi-sidebar,
                        [x-show] .fi-sidebar,
                        .fi-sidebar-content { 
                            overflow-y: auto !important; 
                            -webkit-overflow-scrolling: touch !important;
                            max-height: 100vh !important;
                            height: 100% !important;
                        }
                        /* Mobile overlay sidebar */
                        @media (max-width: 1024px) {
                            .fi-sidebar {
                                overflow-y: auto !important;
                                max-height: 100dvh !important;
                                height: 100dvh !important;
                                padding-bottom: 4rem !important;
                            }
                            .fi-sidebar-nav {
                                overflow-y: auto !important;
                                max-height: calc(100dvh - 5rem) !important;
                                padding-bottom: 6rem !important;
                            }
                            /* Compact items */
                            .fi-sidebar-item { padding-top: 0.15rem !important; padding-bottom: 0.15rem !important; }
                            .fi-sidebar-item-label { font-size: 0.85rem !important; }
                            .fi-sidebar-group-label { padding-top: 0.4rem !important; padding-bottom: 0.2rem !important; font-size: 0.7rem !important; }
                        }
                    </style>
                ')
            )
            ->renderHook(
                'panels::topbar.start',
                fn () => new HtmlString('<a href="/" class="flex items-center gap-1 text-sm text-gray-400 hover:text-white transition px-3"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg> Website</a>')
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                Widgets\AccountWidget::class,
                \App\Filament\Widgets\StatsOverview::class,
                \App\Filament\Widgets\TrackerStatsWidget::class,
                \App\Filament\Widgets\DonationStatsWidget::class,
                \App\Filament\Widgets\FastDlStatsWidget::class,
                \App\Filament\Widgets\FastDlStatsWidget::class,
                \App\Filament\Widgets\PendingUploadsWidget::class,
                \App\Filament\Widgets\LatestUploadsWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->globalSearchKeyBindings(['command+k', 'ctrl+k']);
    }
}
