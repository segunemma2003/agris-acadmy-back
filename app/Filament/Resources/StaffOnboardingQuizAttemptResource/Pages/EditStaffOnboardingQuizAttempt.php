<?php

namespace App\Filament\Resources\StaffOnboardingQuizAttemptResource\Pages;

use App\Filament\Resources\StaffOnboardingQuizAttemptResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStaffOnboardingQuizAttempt extends EditRecord
{
    protected static string $resource = StaffOnboardingQuizAttemptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
