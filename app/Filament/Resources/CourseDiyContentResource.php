<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseDiyContentResource\Pages;
use App\Filament\Tutor\Resources\CourseDiyContentResource as TutorDiy;
use Illuminate\Database\Eloquent\Builder;

/** Admin: every course's DIY practicals (same form as tutors). */
class CourseDiyContentResource extends TutorDiy
{
    protected static ?string $navigationGroup = 'Course Management';

    public static function courseScope(Builder $query): Builder
    {
        return $query;
    }

    public static function submissionsUrl($record): string
    {
        return CourseDiySubmissionResource::getUrl('index', ['tableFilters' => ['course_diy_content_id' => ['value' => $record->id]]]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourseDiyContents::route('/'),
            'create' => Pages\CreateCourseDiyContent::route('/create'),
            'edit' => Pages\EditCourseDiyContent::route('/{record}/edit'),
        ];
    }
}
