<?php

namespace App\Filament\Resources\EnrollmentCodeResource\Pages;

use App\Filament\Resources\EnrollmentCodeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEnrollmentCodes extends ListRecords
{
    protected static string $resource = EnrollmentCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}



