<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseDiySubmissionResource\Pages;
use App\Filament\Tutor\Resources\CourseDiySubmissionResource as TutorSubmissions;
use Illuminate\Database\Eloquent\Builder;

/** Admin: every learner's DIY work. */
class CourseDiySubmissionResource extends TutorSubmissions
{
    protected static ?string $navigationGroup = 'Course Management';

    public static function courseScope(Builder $query): Builder
    {
        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourseDiySubmissions::route('/'),
            'edit' => Pages\EditCourseDiySubmission::route('/{record}/edit'),
        ];
    }
}
