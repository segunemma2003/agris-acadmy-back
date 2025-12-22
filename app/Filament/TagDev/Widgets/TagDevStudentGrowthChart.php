<?php

namespace App\Filament\TagDev\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TagDevStudentGrowthChart extends ChartWidget
{
    protected static ?string $heading = 'Student Growth (Last 6 Months)';

    protected static ?int $sort = 5;

    protected function getData(): array
    {
        // Get cumulative student count by month (database-agnostic)
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite uses strftime
            $students = User::where('role', 'student')
                ->select(
                    DB::raw("strftime('%Y-%m', created_at) as month"),
                    DB::raw('COUNT(*) as count')
                )
                ->where('created_at', '>=', now()->subMonths(6))
                ->groupBy('month')
                ->orderBy('month')
                ->get();
        } else {
            // MySQL/PostgreSQL use DATE_FORMAT
            $students = User::where('role', 'student')
                ->select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                    DB::raw('COUNT(*) as count')
                )
                ->where('created_at', '>=', now()->subMonths(6))
                ->groupBy('month')
                ->orderBy('month')
                ->get();
        }

        $labels = [];
        $data = [];
        
        // Fill all 6 months
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i)->format('Y-m');
            $monthLabel = now()->subMonths($i)->format('M Y');
            $labels[] = $monthLabel;
            
            $student = $students->firstWhere('month', $month);
            $data[] = $student ? $student->count : 0;
        }

        // Calculate cumulative totals
        $cumulative = [];
        $total = 0;
        foreach ($data as $count) {
            $total += $count;
            $cumulative[] = $total;
        }

        return [
            'datasets' => [
                [
                    'label' => 'New Students',
                    'data' => $data,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.5)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                    'type' => 'bar',
                ],
                [
                    'label' => 'Total Students (Cumulative)',
                    'data' => $cumulative,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.3)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'type' => 'line',
                    'fill' => false,
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
                ],
            ],
        ];
    }
}
