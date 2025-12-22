<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureUserIsTagDev;
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

class TagDevPanelProvider extends PanelProvider
{
    public function register(): void
    {
        parent::register();
        
        FilamentView::registerRenderHook(
            'panels::head.end',
            fn (): View => view('filament.tagdev.custom-styles'),
        );
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('tagdev')
            ->path('tagdev')
            ->login()
            ->profile()
            ->colors([
                'primary' => Color::Green,
                'gray' => Color::Slate,
            ])
            ->brandName('Agrisiti & TagDev')
            ->favicon(asset('images/favicon.ico'))
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->spa()
            ->darkMode()
            ->topNavigation(false)
            ->discoverResources(in: app_path('Filament/TagDev/Resources'), for: 'App\\Filament\\TagDev\\Resources')
            ->discoverPages(in: app_path('Filament/TagDev/Pages'), for: 'App\\Filament\\TagDev\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/TagDev/Widgets'), for: 'App\\Filament\\TagDev\\Widgets')
            ->widgets([
                \App\Filament\TagDev\Widgets\TagDevStatsWidget::class,
                \App\Filament\TagDev\Widgets\TagDevEnrollmentChart::class,
                \App\Filament\TagDev\Widgets\TagDevEnrollmentStatusChart::class,
                \App\Filament\TagDev\Widgets\TagDevWeeklyReportChart::class,
                \App\Filament\TagDev\Widgets\TagDevStudentGrowthChart::class,
                \App\Filament\TagDev\Widgets\TagDevCourseEnrollmentChart::class,
                Widgets\AccountWidget::class,
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
                EnsureUserIsTagDev::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('web')
            ->authPasswordBroker('users');
    }
}
