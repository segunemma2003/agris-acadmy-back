<?php

namespace App\Filament\TagDev\Resources\EnrollmentResource\Pages;

use App\Filament\TagDev\Resources\EnrollmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEnrollments extends ListRecords
{
    protected static string $resource = EnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No actions - read-only dashboard
        ];
    }
}
