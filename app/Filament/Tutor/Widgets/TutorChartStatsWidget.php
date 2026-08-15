<?php

namespace App\Filament\Tutor\Widgets;

use App\Models\Course;
use App\Models\Enrollment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TutorChartStatsWidget extends ChartWidget
{
    protected static ?string $heading = 'My Course Enrollments (Last 7 Days)';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $courseIds = Course::query()->accessibleByTutor(Auth::id())->pluck('id');

        $enrollments = Enrollment::select(
            DB::raw('DATE(enrollments.created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->whereIn('course_id', $courseIds)
            ->where('enrollments.created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $data = $enrollments->pluck('count')->toArray();
        $dates = $enrollments->pluck('date')->toArray();

        $fullLabels = [];
        $fullData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $fullLabels[] = now()->subDays($i)->format('M d');
            $fullData[] = $data[array_search($date, $dates)] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Enrollments',
                    'data' => $fullData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.5)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
            ],
            'labels' => $fullLabels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
