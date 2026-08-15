<?php

namespace App\Filament\Facilitator\Resources\CourseVrContentResource\Pages;

use App\Filament\Concerns\InteractsWithVrStudio;
use App\Filament\Facilitator\Resources\CourseVrContentResource;
use App\Models\CourseVrContent;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCourseVrContent extends EditRecord
{
    use InteractsWithVrStudio;

    protected static string $resource = CourseVrContentResource::class;

    protected function getHeaderActions(): array
    {
        /** @var CourseVrContent $record */
        $record = $this->record;

        return [
            $this->vrStudioHeaderActionForRecord($record),
            Actions\ViewAction::make(),
        ];
    }
}
