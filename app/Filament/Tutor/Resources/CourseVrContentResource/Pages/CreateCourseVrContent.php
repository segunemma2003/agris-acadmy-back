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
        $data['tutor_id'] = Auth::id();
        $data['studio_status'] = $data['studio_status'] ?? 'draft';
        $data['cta_label'] = $data['cta_label'] ?? 'Launch VR';
        if (empty($data['vr_url'])) {
            unset($data['vr_url']);
        }

        return $data;
    }
}

