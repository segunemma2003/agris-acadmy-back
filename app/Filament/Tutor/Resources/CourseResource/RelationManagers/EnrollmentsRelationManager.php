<?php

namespace App\Filament\Tutor\Resources\CourseResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';
}

