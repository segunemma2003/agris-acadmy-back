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
        // Pricing is platform-admin only; tutors author content only.
        $data['price'] = $data['price'] ?? 0;
        $data['is_free'] = $data['is_free'] ?? true;
        $data['is_featured'] = false;

        return $data;
    }
}
