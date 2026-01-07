<?php

namespace App\Filament\Supervisor\Resources\CourseVrContentResource\Pages;

use App\Filament\Supervisor\Resources\CourseVrContentResource;
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

