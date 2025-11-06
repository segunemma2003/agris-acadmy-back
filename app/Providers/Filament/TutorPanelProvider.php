<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureUserIsTutor;
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

class TutorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('tutor')
            ->path('tutor')
            ->login()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Tutor/Resources'), for: 'App\\Filament\\Tutor\\Resources')
            ->discoverPages(in: app_path('Filament/Tutor/Pages'), for: 'App\\Filament\\Tutor\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Tutor/Widgets'), for: 'App\\Filament\\Tutor\\Widgets')
            ->widgets([
                \App\Filament\Tutor\Widgets\TutorStatsWidget::class,
                Widgets\AccountWidget::class,
            ])
            ->brandName('Tutor Dashboard')
            ->brandLogo(asset('images/logo.svg'))
            ->favicon(asset('images/favicon.ico'))
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                'Course Management',
                'Student Management',
                'Communication',
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
                EnsureUserIsTutor::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('web')
            ->authPasswordBroker('users');
    }
}

