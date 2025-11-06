<?php

namespace App\Filament\Tutor\Widgets;

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
        $tutorId = Auth::id();

        // Get enrollments for tutor's courses for the last 7 days
        $enrollments = Enrollment::select(
            DB::raw('DATE(enrollments.created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
        ->whereHas('course', function ($query) use ($tutorId) {
            $query->where('tutor_id', $tutorId);
        })
        ->where('enrollments.created_at', '>=', now()->subDays(7))
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        $data = $enrollments->pluck('count')->toArray();
        $dates = $enrollments->pluck('date')->toArray();

        // Fill missing days with 0
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
