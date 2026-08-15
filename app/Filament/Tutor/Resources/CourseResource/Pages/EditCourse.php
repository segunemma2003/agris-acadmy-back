<?php

namespace App\Filament\Tutor\Resources\CourseResource\Pages;

use App\Filament\Tutor\Resources\CourseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCourse extends EditRecord
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Tutors cannot change ownership, pricing, or featured status.
        unset($data['tutor_id'], $data['tutors'], $data['price'], $data['is_free'], $data['is_featured']);

        return $data;
    }
}
