<?php

namespace App\Filament\Concerns;

use App\Models\CourseVrContent;
use App\Services\VrStudioService;
use Filament\Notifications\Notification;
use Filament\Tables;
use Illuminate\Support\Facades\Auth;

trait InteractsWithVrStudio
{
    public static function openVrStudioTableAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('openVrStudio')
            ->label('Open VR Studio')
            ->icon('heroicon-o-cube-transparent')
            ->color('success')
            ->url(function (CourseVrContent $record) {
                $user = Auth::user();
                if (! $user) {
                    return null;
                }

                return app(VrStudioService::class)->createHandoffUrl($user, $record);
            }, shouldOpenInNewTab: true)
            ->visible(fn () => filled(config('services.vr_studio.url')));
    }

    public static function openVrStudioHeaderAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('openVrStudio')
            ->label('Open VR Studio')
            ->icon('heroicon-o-cube-transparent')
            ->color('success')
            ->url(function () {
                $record = method_exists(static::class, 'getRecord') ? null : null;
                // Resolved on page classes that set $this->record
                return null;
            })
            ->visible(false);
    }

    protected function vrStudioHeaderActionForRecord(CourseVrContent $record): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('openVrStudio')
            ->label('Open VR Studio')
            ->icon('heroicon-o-cube-transparent')
            ->color('success')
            ->url(function () use ($record) {
                $user = Auth::user();
                if (! $user) {
                    Notification::make()->title('Not signed in')->danger()->send();

                    return null;
                }

                return app(VrStudioService::class)->createHandoffUrl($user, $record);
            }, shouldOpenInNewTab: true)
            ->visible(fn () => filled(config('services.vr_studio.url')));
    }
}
