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
use Filament\Support\Facades\FilamentView;
use Illuminate\Contracts\View\View;

class TutorPanelProvider extends PanelProvider
{
    public function register(): void
    {
        parent::register();

        FilamentView::registerRenderHook(
            'panels::head.end',
            fn (): View => view('filament.tutor.custom-styles'),
        );
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('tutor')
            ->path('tutor')
            ->login()
            ->profile()
            ->colors([
                'primary' => Color::Green,
                'gray' => Color::Slate,
            ])
            ->discoverResources(in: app_path('Filament/Tutor/Resources'), for: 'App\\Filament\\Tutor\\Resources')
            ->discoverPages(in: app_path('Filament/Tutor/Pages'), for: 'App\\Filament\\Tutor\\Pages')
            ->pages([
                Pages\Dashboard::class,
                \App\Filament\Tutor\Pages\StaffOnboardingQuiz::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Tutor/Widgets'), for: 'App\\Filament\\Tutor\\Widgets')
            ->widgets([
                \App\Filament\Tutor\Widgets\TutorStatsWidget::class,
                \App\Filament\Tutor\Widgets\TutorChartStatsWidget::class,
                \App\Filament\Tutor\Widgets\TutorTableStatsWidget::class,
                Widgets\AccountWidget::class,
            ])
            ->brandName('Agrisiti')
            ->favicon(asset('images/favicon.ico'))
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                'Course Management', // 'heroicon-o-academic-cap',
                'Student Management', // 'heroicon-o-user-group',
                'Communication', // 'heroicon-o-chat-bubble-left-right',
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
                EnsureUserIsTutor::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('web')
            ->authPasswordBroker('users');
    }
}

