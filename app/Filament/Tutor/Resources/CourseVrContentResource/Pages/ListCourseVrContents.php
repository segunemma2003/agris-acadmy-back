<?php

namespace App\Filament\Tutor\Resources\CourseVrContentResource\Pages;

use App\Filament\Tutor\Resources\CourseVrContentResource;
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

