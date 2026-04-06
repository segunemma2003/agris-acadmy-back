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
        $actualTotalUsers = User::count();
        $totalTutors = User::where('role', 'tutor')->count();
        $totalCourses = Course::count();
        $publishedCourses = Course::where('is_published', true)->count();
        $totalEnrollments = Enrollment::count();
        $activeEnrollments = Enrollment::where('status', 'active')->count();
        $completedEnrollments = Enrollment::where('status', 'completed')->count();

        $totalOnlineEnrolled = 2623;
        $activeLearners = 2449;
        $teamsFormed = 301;
        $demoHubVisits = 116;

        // Use provided platform metrics for display
        $displayTotalUsers = $totalOnlineEnrolled;
        $activeStaff = User::whereIn('role', ['admin', 'tutor', 'facilitator', 'tagdev'])
            ->where('is_active', true)
            ->count();
        $activeUsersDisplay = $activeLearners + $activeStaff;

        // Calculate revenue (if you have payment tracking)
        $totalRevenue = Enrollment::sum('amount_paid') ?? 0;

        return [
            Stat::make('Total Users', $displayTotalUsers)
                ->description('Platform users')
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->chart([7, 12, 15, 18, 22, 25, $displayTotalUsers]),

            Stat::make('Total Courses', $totalCourses)
                ->description("{$publishedCourses} published")
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info')
                ->chart([3, 5, 8, 12, 15, 18, $totalCourses]),

            // Stat::make('Total Enrollments', $totalEnrollments)
            //     ->description("Records: {$activeEnrollments} active, {$completedEnrollments} completed")
            //     ->descriptionIcon('heroicon-m-user-group')
            //     ->color('warning')
            //     ->chart([10, 20, 30, 40, 50, 60, $totalEnrollments]),

            Stat::make('Total Online Enrolled', $totalOnlineEnrolled)
                ->description('Online enrolled learners')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Active Users', $activeUsersDisplay)
                ->description('Active learners + active staff')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('warning'),

            Stat::make('Teams Formed', $teamsFormed)
                ->description('Total teams formed')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Demo Hub Visits', $demoHubVisits)
                ->description('Total demo hub visits')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('primary'),

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

