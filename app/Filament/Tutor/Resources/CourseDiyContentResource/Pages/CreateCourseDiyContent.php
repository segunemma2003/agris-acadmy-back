<?php

namespace App\Filament\Tutor\Resources\CourseDiyContentResource\Pages;

use App\Filament\Tutor\Resources\CourseDiyContentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCourseDiyContent extends CreateRecord
{
    protected static string $resource = CourseDiyContentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tutor_id'] = \Illuminate\Support\Facades\Auth::id();

        return $data;
    }
}
