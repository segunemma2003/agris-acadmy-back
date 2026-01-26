<?php

namespace App\Filament\Facilitator\Resources\CourseVrContentResource\Pages;

use App\Filament\Facilitator\Resources\CourseVrContentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCourseVrContents extends ListRecords
{
    protected static string $resource = CourseVrContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

