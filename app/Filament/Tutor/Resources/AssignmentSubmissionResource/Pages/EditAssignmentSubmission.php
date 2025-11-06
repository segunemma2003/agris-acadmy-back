<?php

namespace App\Filament\Tutor\Resources\AssignmentSubmissionResource\Pages;

use App\Filament\Tutor\Resources\AssignmentSubmissionResource;
use Filament\Resources\Pages\EditRecord;

class EditAssignmentSubmission extends EditRecord
{
    protected static string $resource = AssignmentSubmissionResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['score']) && $data['status'] === 'pending') {
            $data['status'] = 'graded';
            $data['graded_at'] = now();
        }
        return $data;
    }
}

