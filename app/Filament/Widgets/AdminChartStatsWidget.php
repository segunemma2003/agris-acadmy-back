<?php

namespace App\Filament\Widgets;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class AdminChartStatsWidget extends ChartWidget
{
    protected static ?string $heading = 'Enrollment Trends';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        // Get enrollments for the last 7 days
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            $enrollments = Enrollment::select(
                DB::raw("DATE(created_at) as date"),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy(DB::raw("DATE(created_at)"))
            ->orderBy('date')
            ->get();
        } else {
            $enrollments = Enrollment::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        }

        $labels = $enrollments->pluck('date')->map(fn ($date) => date('M d', strtotime($date)))->toArray();
        $data = $enrollments->pluck('count')->toArray();

        // Fill missing days with 0
        $fullLabels = [];
        $fullData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $fullLabels[] = now()->subDays($i)->format('M d');
            $fullData[] = $data[array_search($date, $enrollments->pluck('date')->toArray())] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Enrollments',
                    'data' => $fullData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
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
