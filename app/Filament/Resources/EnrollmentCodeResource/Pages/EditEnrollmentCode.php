<?php

namespace App\Filament\Resources\EnrollmentCodeResource\Pages;

use App\Filament\Resources\EnrollmentCodeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEnrollmentCode extends EditRecord
{
    protected static string $resource = EnrollmentCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}



