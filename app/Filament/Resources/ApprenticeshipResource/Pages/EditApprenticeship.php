<?php

namespace App\Filament\Resources\ApprenticeshipResource\Pages;

use App\Filament\Resources\ApprenticeshipResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditApprenticeship extends EditRecord
{
    protected static string $resource = ApprenticeshipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
