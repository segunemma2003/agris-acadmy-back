<?php

namespace App\Filament\Tutor\Resources\AssignmentSubmissionResource\Pages;

use App\Filament\Tutor\Resources\AssignmentSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssignmentSubmission extends EditRecord
{
    protected static string $resource = AssignmentSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

