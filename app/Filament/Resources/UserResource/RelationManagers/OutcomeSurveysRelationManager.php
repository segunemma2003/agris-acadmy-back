<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Resources\CourseOutcomeSurveyResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/** A learner's end-of-course checks (Part 5). */
class OutcomeSurveysRelationManager extends RelationManager
{
    protected static string $relationship = 'courseOutcomeSurveys';

    protected static ?string $title = 'End-of-course checks (Part 5)';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return CourseOutcomeSurveyResource::table($table)
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View answers')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => CourseOutcomeSurveyResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
