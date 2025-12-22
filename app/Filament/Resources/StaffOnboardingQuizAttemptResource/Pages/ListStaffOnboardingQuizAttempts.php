<?php

namespace App\Filament\Resources\StaffOnboardingQuizAttemptResource\Pages;

use App\Filament\Resources\StaffOnboardingQuizAttemptResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStaffOnboardingQuizAttempts extends ListRecords
{
    protected static string $resource = StaffOnboardingQuizAttemptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
