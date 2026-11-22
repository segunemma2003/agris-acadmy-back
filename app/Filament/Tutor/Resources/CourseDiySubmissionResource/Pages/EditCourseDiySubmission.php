<?php

namespace App\Filament\Tutor\Resources\CourseDiySubmissionResource\Pages;

use App\Filament\Tutor\Resources\CourseDiySubmissionResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditCourseDiySubmission extends EditRecord
{
    protected static string $resource = CourseDiySubmissionResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (in_array($data['status'] ?? '', ['approved', 'needs_work'], true)) {
            $data['reviewed_by'] = Auth::id();
            $data['reviewed_at'] = now();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
