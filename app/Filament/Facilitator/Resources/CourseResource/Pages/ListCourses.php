<?php

namespace App\Filament\Facilitator\Resources\CourseResource\Pages;

use App\Filament\Facilitator\Resources\CourseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCourses extends ListRecords
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}

