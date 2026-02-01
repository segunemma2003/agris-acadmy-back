<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureUserIsFacilitator;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Support\Facades\FilamentView;
use Illuminate\Contracts\View\View;

class FacilitatorPanelProvider extends PanelProvider
{
    public function register(): void
    {
        parent::register();

        FilamentView::registerRenderHook(
            'panels::head.end',
            fn (): View => view('filament.facilitator.custom-styles'),
        );
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('facilitator')
            ->path('facilitator')
            ->login()
            ->profile()
            ->colors([
                'primary' => Color::Green,
                'gray' => Color::Slate,
            ])
            ->discoverResources(in: app_path('Filament/Facilitator/Resources'), for: 'App\\Filament\\Facilitator\\Resources')
            ->discoverPages(in: app_path('Filament/Facilitator/Pages'), for: 'App\\Filament\\Facilitator\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Facilitator/Widgets'), for: 'App\\Filament\\Facilitator\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->brandName('Agrisiti')
            ->favicon(asset('images/favicon.ico'))
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                'Course Management',
                'Student Management',
                'Reports',
            ])
            ->maxContentWidth('full')
            ->spa()
            ->darkMode()
            ->topNavigation(false)
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
                EnsureUserIsFacilitator::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('web')
            ->authPasswordBroker('users');
    }
}
