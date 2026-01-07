<?php

namespace App\Filament\Supervisor\Resources\CourseResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';
}

