<?php

namespace App\Filament\Facilitator\Resources\CourseResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';
}

