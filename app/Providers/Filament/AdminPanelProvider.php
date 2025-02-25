<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use App\Settings\SiteSettings;
use Filament\Support\Assets\Css;
use App\Filament\Pages\Auth\Login;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Vite;
use Filament\Http\Middleware\Authenticate;
use Filament\FontProviders\GoogleFontProvider;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->maxContentWidth('full')
            ->brandName(function () {
                $settings = app(SiteSettings::class) ?? null;
                if ($settings) {
                    return $settings->site_name;
                }

                return 'Filament';
            })
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->assets([
                Css::make('custom-style-sheet',
                    Vite::useHotFile('app.hot')
                        ->useBuildDirectory('app')
                        ->asset('resources/css/app.css', 'app')
                ),
            ])
            ->font('Inter', provider: GoogleFontProvider::class)
            ->passwordReset()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->breadcrumbs()
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
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
            ]);
    }
}
