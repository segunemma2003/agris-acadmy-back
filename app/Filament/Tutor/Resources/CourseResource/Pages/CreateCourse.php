<?php

namespace App\Filament\Tutor\Resources\CourseResource\Pages;

use App\Filament\Tutor\Resources\CourseResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateCourse extends CreateRecord
{
    protected static string $resource = CourseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tutor_id'] = Auth::id();
        return $data;
    }
}

