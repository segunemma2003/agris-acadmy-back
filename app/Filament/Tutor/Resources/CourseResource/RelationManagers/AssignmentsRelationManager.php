<?php

namespace App\Filament\Tutor\Resources\CourseResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';
}

