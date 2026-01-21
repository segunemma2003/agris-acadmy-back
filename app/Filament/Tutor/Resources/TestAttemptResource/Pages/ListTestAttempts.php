<?php

namespace App\Filament\Tutor\Resources\TestAttemptResource\Pages;

use App\Filament\Tutor\Resources\TestAttemptResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTestAttempts extends ListRecords
{
    protected static string $resource = TestAttemptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Test attempts are created by students, not tutors
        ];
    }
}
