<?php

namespace App\Filament\TagDev\Widgets;

use App\Models\Enrollment;
use Filament\Widgets\ChartWidget;

class TagDevEnrollmentStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Enrollment Status Distribution';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $active = Enrollment::where('status', 'active')->count();
        $completed = Enrollment::where('status', 'completed')->count();
        $cancelled = Enrollment::where('status', 'cancelled')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Enrollments by Status',
                    'data' => [$active, $completed, $cancelled],
                    'backgroundColor' => [
                        'rgba(34, 197, 94, 0.8)',    // Green for active
                        'rgba(59, 130, 246, 0.8)',   // Blue for completed
                        'rgba(239, 68, 68, 0.8)',    // Red for cancelled
                    ],
                    'borderColor' => [
                        'rgb(34, 197, 94)',
                        'rgb(59, 130, 246)',
                        'rgb(239, 68, 68)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => ['Active', 'Completed', 'Cancelled'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}












