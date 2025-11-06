<?php

namespace App\Filament\Widgets;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalUsers = User::count();
        $totalTutors = User::where('role', 'tutor')->count();
        $totalStudents = User::where('role', 'student')->count();
        $totalCourses = Course::count();
        $publishedCourses = Course::where('is_published', true)->count();
        $totalEnrollments = Enrollment::count();
        $activeEnrollments = Enrollment::where('status', 'active')->count();
        $completedEnrollments = Enrollment::where('status', 'completed')->count();

        // Calculate revenue (if you have payment tracking)
        $totalRevenue = Enrollment::sum('amount_paid');

        return [
            Stat::make('Total Users', $totalUsers)
                ->description('All registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->chart([7, 12, 15, 18, 22, 25, $totalUsers]),

            Stat::make('Total Courses', $totalCourses)
                ->description("{$publishedCourses} published")
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info')
                ->chart([3, 5, 8, 12, 15, 18, $totalCourses]),

            Stat::make('Total Enrollments', $totalEnrollments)
                ->description("{$activeEnrollments} active, {$completedEnrollments} completed")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning')
                ->chart([10, 20, 30, 40, 50, 60, $totalEnrollments]),

            Stat::make('Tutors', $totalTutors)
                ->description("{$totalStudents} students")
                ->descriptionIcon('heroicon-m-user-circle')
                ->color('primary'),

            Stat::make('Total Revenue', '$' . number_format($totalRevenue, 2))
                ->description('From all enrollments')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
        ];
    }
}

