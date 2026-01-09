<?php

namespace App\Filament\Resources\CourseResource\Pages;

use App\Filament\Resources\CourseResource;
use Filament\Resources\Pages\EditRecord;

class EditCourse extends EditRecord
{
    protected static string $resource = CourseResource::class;

    protected ?array $tutors = null;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Extract tutors if provided
        $tutors = $data['tutors'] ?? [];
        unset($data['tutors']);
        
        // Store tutors for afterSave
        $this->tutors = $tutors;
        
        return $data;
    }

    protected function afterSave(): void
    {
        // Sync tutors after course is saved
        if (isset($this->tutors)) {
            $this->record->tutors()->sync($this->tutors);
        }
    }
}

