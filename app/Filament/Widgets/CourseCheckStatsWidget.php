<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CourseOutcomeSurveyResource;
use App\Models\CourseOutcomeSurvey;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Admin dashboard: headline results from learners' end-of-course checks. */
class CourseCheckStatsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'End-of-course checks';

    protected function getStats(): array
    {
        $all = CourseOutcomeSurvey::query();
        $total = (clone $all)->count();
        $pct = fn (int $n) => $total ? round($n / $total * 100).'%' : '—';
        $url = CourseOutcomeSurveyResource::getUrl('index');

        return [
            Stat::make('Responses', number_format($total))
                ->description((clone $all)->where('stage', 'completion')->count().' at completion · '.(clone $all)->where('stage', 'six_month')->count().' at six months')
                ->url($url),
            Stat::make('Would recommend', $pct((clone $all)->where('would_recommend', true)->count()))
                ->description('of learners who answered')
                ->color('success')
                ->url($url),
            Stat::make('Rated good or excellent', $pct((clone $all)->whereIn('rating', ['good', 'excellent'])->count()))
                ->url($url),
            Stat::make('Started an agri-enterprise', $pct((clone $all)->where('started_enterprise', true)->count()))
                ->url($url),
            Stat::make('New job or better pay', $pct((clone $all)->whereIn('work_change', ['new_job', 'better_pay'])->count()))
                ->url($url),
        ];
    }
}
