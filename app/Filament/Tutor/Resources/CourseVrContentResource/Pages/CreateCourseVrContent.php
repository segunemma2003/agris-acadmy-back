<?php

namespace App\Filament\Tutor\Resources\CourseVrContentResource\Pages;

use App\Filament\Tutor\Resources\CourseVrContentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateCourseVrContent extends CreateRecord
{
    protected static string $resource = CourseVrContentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set tutor_id when creating VR content
        $data['tutor_id'] = Auth::id();
        return $data;
    }
}

