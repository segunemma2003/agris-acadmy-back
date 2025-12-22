<?php

namespace App\Filament\Resources\StaffOnboardingQuizAttemptResource\Pages;

use App\Filament\Resources\StaffOnboardingQuizAttemptResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewStaffOnboardingQuizAttempt extends ViewRecord
{
    protected static string $resource = StaffOnboardingQuizAttemptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
