<?php

namespace App\Filament\TagDev\Widgets;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\WeeklyReport;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TagDevStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalStudents = User::where('role', 'student')->count();
        $totalTutors = User::where('role', 'tutor')->count();
        $totalCourses = Course::where('is_published', true)->count();
        $totalEnrollments = Enrollment::where('status', 'active')->count();
        $completedEnrollments = Enrollment::where('status', 'completed')->count();
        
        // Weekly reports statistics
        $totalReports = WeeklyReport::count();
        $submittedReports = WeeklyReport::where('status', 'submitted')->count();
        $thisWeekReports = WeeklyReport::whereBetween('report_week_start', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->count();

        return [
            Stat::make('Total Students', number_format($totalStudents))
                ->description('Active participants')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Total Tutors', number_format($totalTutors))
                ->description('Active staff members')
                ->descriptionIcon('heroicon-m-user-circle')
                ->color('info'),

            Stat::make('Published Courses', number_format($totalCourses))
                ->description('Available courses')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary'),

            Stat::make('Active Enrollments', number_format($totalEnrollments))
                ->description(number_format($completedEnrollments) . ' completed')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('warning'),

            Stat::make('Weekly Reports', number_format($totalReports))
                ->description("{$submittedReports} submitted, {$thisWeekReports} this week")
                ->descriptionIcon('heroicon-m-document-text')
                ->color('success'),
        ];
    }
}
