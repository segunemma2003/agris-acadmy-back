<?php

namespace App\Filament\Tutor\Resources\AssignmentSubmissionResource\Pages;

use App\Filament\Tutor\Resources\AssignmentSubmissionResource;
use Filament\Resources\Pages\EditRecord;

class EditAssignmentSubmission extends EditRecord
{
    protected static string $resource = AssignmentSubmissionResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Auto-set status to 'graded' if score is provided and status is still 'submitted'
        if (isset($data['score']) && $data['score'] !== null && $data['status'] === 'submitted') {
            $data['status'] = 'graded';
            $data['graded_at'] = now();
        }
        
        // Set graded_at timestamp when status changes to 'graded'
        if ($data['status'] === 'graded' && !isset($data['graded_at'])) {
            $data['graded_at'] = now();
        }
        
        return $data;
    }
}

