<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CourseOutcomeSurveyResource;
use App\Models\CourseOutcomeSurvey;
use App\Support\ParticipantProfile as PP;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/** Admin dashboard: the most recent end-of-course checks, linking to each response. */
class LatestCourseChecksWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Latest end-of-course checks';

    public function table(Table $table): Table
    {
        $label = fn (array $options) => fn ($state) => $state === null ? '—' : ($options[$state] ?? $state);

        return $table
            ->query(CourseOutcomeSurvey::query()->with(['user', 'course'])->latest()->limit(8))
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Learner'),
                Tables\Columns\TextColumn::make('course.title')->label('Course')->wrap(),
                Tables\Columns\TextColumn::make('stage')->badge()->formatStateUsing($label(PP::SURVEY_STAGES)),
                Tables\Columns\TextColumn::make('rating')->formatStateUsing($label(PP::RATING)),
                Tables\Columns\TextColumn::make('work_change')->label('Work change')->formatStateUsing($label(PP::WORK_CHANGE))->wrap(),
                Tables\Columns\IconColumn::make('would_recommend')->label('Recommend')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label('Submitted')->since(),
            ])
            ->recordUrl(fn ($record) => CourseOutcomeSurveyResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading('No end-of-course checks yet')
            ->emptyStateDescription('They appear here when learners finish a course and answer the check.')
            ->headerActions([
                Tables\Actions\Action::make('all')->label('View all')->url(CourseOutcomeSurveyResource::getUrl('index')),
            ]);
    }
}
