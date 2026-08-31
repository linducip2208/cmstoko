<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\OrdersChart;
use App\Filament\Widgets\SalesStats;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\View;
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
            ->brandName(config('shop.name', 'TokoKita'))
            ->brandLogo(fn () => View::make('filament.admin.brand'))
            ->brandLogoHeight('2.25rem')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::hex('#9a4a2b'), // terracotta — storefront accent
                'gray' => Color::Stone,
            ])
            ->font('Instrument Sans')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->darkMode(true)
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->navigationGroups([
                'Dasbor',
                'Penjualan',
                'Katalog',
                'Persediaan',
                'Pelanggan',
                'Pemasaran',
                'Konten',
                'Tampilan',
                'SEO',
                'Laporan',
                'Pengaturan',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                SalesStats::class,
                OrdersChart::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
