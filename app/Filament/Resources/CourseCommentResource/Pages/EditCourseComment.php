<?php

namespace App\Filament\Resources\CourseCommentResource\Pages;

use App\Filament\Resources\CourseCommentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCourseComment extends EditRecord
{
    protected static string $resource = CourseCommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
