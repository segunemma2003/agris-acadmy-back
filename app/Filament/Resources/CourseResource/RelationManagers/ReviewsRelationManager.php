<?php

namespace App\Filament\Resources\CourseResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;

class ReviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'reviews';

    /** Editable on the View page too, not just Edit (Filament defaults View tabs to read-only). */
    public function isReadOnly(): bool
    {
        return false;
    }
}

