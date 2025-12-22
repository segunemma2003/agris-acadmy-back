<?php

namespace App\Filament\TagDev\Widgets;

use App\Models\WeeklyReport;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TagDevWeeklyReportChart extends ChartWidget
{
    protected static ?string $heading = 'Weekly Report Submissions (Last 12 Weeks)';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        // Get reports for the last 12 weeks (database-agnostic)
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            $reports = WeeklyReport::select(
                DB::raw("date(report_week_start) as week_start"),
                DB::raw('COUNT(*) as count')
            )
            ->where('report_week_start', '>=', now()->subWeeks(12)->startOfWeek())
            ->groupBy('week_start')
            ->orderBy('week_start')
            ->get();
        } else {
            $reports = WeeklyReport::select(
                DB::raw('DATE(report_week_start) as week_start'),
                DB::raw('COUNT(*) as count')
            )
            ->where('report_week_start', '>=', now()->subWeeks(12)->startOfWeek())
            ->groupBy('week_start')
            ->orderBy('week_start')
            ->get();
        }

        $labels = [];
        $data = [];
        
        // Fill all 12 weeks
        for ($i = 11; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekLabel = $weekStart->format('M d');
            $labels[] = $weekLabel;
            
            $report = $reports->firstWhere('week_start', $weekStart->format('Y-m-d'));
            $data[] = $report ? $report->count : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Reports Submitted',
                    'data' => $data,
                    'backgroundColor' => 'rgba(251, 191, 36, 0.5)',
                    'borderColor' => 'rgb(251, 191, 36)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}

