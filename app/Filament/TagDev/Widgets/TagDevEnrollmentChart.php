<?php

namespace App\Filament\TagDev\Widgets;

use App\Models\Enrollment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TagDevEnrollmentChart extends ChartWidget
{
    protected static ?string $heading = 'Enrollment Trends (Last 30 Days)';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        // Get enrollments for the last 30 days (database-agnostic)
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            $enrollments = Enrollment::select(
                DB::raw("date(created_at) as date"),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        } else {
            $enrollments = Enrollment::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        }

        $labels = [];
        $data = [];
        
        // Fill all 30 days
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dateLabel = now()->subDays($i)->format('M d');
            $labels[] = $dateLabel;
            
            $enrollment = $enrollments->firstWhere('date', $date);
            $data[] = $enrollment ? $enrollment->count : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Enrollments',
                    'data' => $data,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.5)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
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
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
        ];
    }
}

