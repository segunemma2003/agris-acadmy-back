<?php

namespace App\Filament\Resources\CourseResource\Pages;

use App\Filament\Resources\CourseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCourse extends CreateRecord
{
    protected static string $resource = CourseResource::class;

    protected ?array $tutors = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Extract tutors if provided
        $tutors = $data['tutors'] ?? [];
        unset($data['tutors']);
        
        // Store tutors for afterCreate
        $this->tutors = $tutors;
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Sync tutors after course is created
        if (isset($this->tutors) && !empty($this->tutors)) {
            $this->record->tutors()->sync($this->tutors);
        }
    }
}

