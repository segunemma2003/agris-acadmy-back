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
        $registeredStudents = User::where('role', 'student')->count();
        $totalCourses = Course::count();
        $publishedCourses = Course::where('is_published', true)->count();
        $totalEnrollments = Enrollment::count();
        $activeEnrollments = Enrollment::where('status', 'active')->count();
        $completedEnrollments = Enrollment::where('status', 'completed')->count();
        $activeEnrolledStudents = Enrollment::where('status', 'active')
            ->distinct()
            ->count('user_id');

        // Calculate revenue (if you have payment tracking)
        $totalRevenue = Enrollment::sum('amount_paid') ?? 0;

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
                ->description("Records: {$activeEnrollments} active, {$completedEnrollments} completed")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning')
                ->chart([10, 20, 30, 40, 50, 60, $totalEnrollments]),

            Stat::make('Registered Students', $registeredStudents)
                ->description('All users with role = student')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Enrolled Students', $activeEnrolledStudents)
                ->description('Unique students with active enrollments')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('warning'),

            Stat::make('Tutors', $totalTutors)
                ->description('All users with role = tutor')
                ->descriptionIcon('heroicon-m-user-circle')
                ->color('primary'),

            Stat::make('Total Revenue', '$' . number_format($totalRevenue, 2))
                ->description('From all enrollments')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
        ];
    }
}

