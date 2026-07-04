<?php

namespace App\Filament\Resources\CourseCommentResource\Pages;

use App\Filament\Resources\CourseCommentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCourseComments extends ListRecords
{
    protected static string $resource = CourseCommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
