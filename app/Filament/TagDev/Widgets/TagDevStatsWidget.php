<?php

namespace App\Filament\TagDev\Widgets;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\WeeklyReport;
use App\Models\StudentProgress;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Certificate;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class TagDevStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // User Statistics
        $totalStudents = User::where('role', 'student')->count();
        $activeStudents = User::where('role', 'student')->where('is_active', true)->count();
        $totalTutors = User::where('role', 'tutor')->count();
        $activeTutors = User::where('role', 'tutor')->where('is_active', true)->count();
        $totalFacilitators = User::where('role', 'facilitator')->count();
        $newStudentsThisMonth = User::where('role', 'student')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        
        // Course Statistics
        $totalCourses = Course::count();
        $publishedCourses = Course::where('is_published', true)->count();
        $featuredCourses = Course::where('is_featured', true)->count();
        $totalModules = DB::table('modules')->count();
        $totalTopics = DB::table('topics')->count();
        
        // Enrollment Statistics
        $totalEnrollments = Enrollment::count();
        $activeEnrollments = Enrollment::where('status', 'active')->count();
        $completedEnrollments = Enrollment::where('status', 'completed')->count();
        $enrollmentsThisMonth = Enrollment::whereMonth('enrolled_at', now()->month)
            ->whereYear('enrolled_at', now()->year)
            ->count();
        
        // Progress Statistics
        $totalProgress = StudentProgress::count();
        $completedProgress = StudentProgress::where('is_completed', true)->count();
        $totalWatchTime = StudentProgress::sum('watch_time_seconds');
        $totalWatchHours = round($totalWatchTime / 3600, 1);
        
        // Assignment Statistics
        $totalAssignments = Assignment::count();
        $totalSubmissions = AssignmentSubmission::count();
        $gradedSubmissions = AssignmentSubmission::where('status', 'graded')->count();
        $pendingSubmissions = AssignmentSubmission::where('status', 'submitted')->count();
        
        // Certificate Statistics
        $totalCertificates = Certificate::count();
        $certificatesThisMonth = Certificate::whereMonth('issued_date', now()->month)
            ->whereYear('issued_date', now()->year)
            ->count();
        
        // Weekly Reports Statistics
        $totalReports = WeeklyReport::count();
        $submittedReports = WeeklyReport::where('status', 'submitted')->count();
        $reviewedReports = WeeklyReport::where('status', 'reviewed')->count();
        $thisWeekReports = WeeklyReport::whereBetween('report_week_start', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->count();
        
        // Calculate completion rate
        $completionRate = $totalEnrollments > 0 
            ? round(($completedEnrollments / $totalEnrollments) * 100, 1) 
            : 0;
        
        // Calculate average enrollments per course
        $avgEnrollmentsPerCourse = $publishedCourses > 0 
            ? round($activeEnrollments / $publishedCourses, 1) 
            : 0;

        return [
            Stat::make('Total Students', number_format($totalStudents))
                ->description("{$activeStudents} active • {$newStudentsThisMonth} new this month")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5]),

            Stat::make('Total Tutors', number_format($totalTutors))
                ->description("{$activeTutors} active tutors")
                ->descriptionIcon('heroicon-m-user-circle')
                ->color('info')
                ->chart([2, 1, 3, 2, 4, 3, 2]),

            Stat::make('Facilitators', number_format($totalFacilitators))
                ->description('Active facilitators')
                ->descriptionIcon('heroicon-m-users')
                ->color('warning'),

            Stat::make('Published Courses', number_format($publishedCourses))
                ->description("{$featuredCourses} featured • {$totalCourses} total")
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary')
                ->chart([5, 3, 4, 6, 5, 4, 7]),

            Stat::make('Course Content', number_format($totalModules + $totalTopics))
                ->description("{$totalModules} modules • {$totalTopics} topics")
                ->descriptionIcon('heroicon-m-book-open')
                ->color('success'),

            Stat::make('Active Enrollments', number_format($activeEnrollments))
                ->description("{$completedEnrollments} completed • {$enrollmentsThisMonth} this month")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('warning')
                ->chart([10, 15, 12, 18, 20, 16, 22]),

            Stat::make('Completion Rate', $completionRate . '%')
                ->description("{$completedEnrollments} of {$totalEnrollments} enrollments")
                ->descriptionIcon('heroicon-m-trophy')
                ->color('success'),

            Stat::make('Avg Enrollments/Course', number_format($avgEnrollmentsPerCourse))
                ->description('Average per published course')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            Stat::make('Total Watch Time', number_format($totalWatchHours) . ' hours')
                ->description("{$completedProgress} completed lessons")
                ->descriptionIcon('heroicon-m-play-circle')
                ->color('primary'),

            Stat::make('Assignments', number_format($totalAssignments))
                ->description("{$totalSubmissions} submissions • {$gradedSubmissions} graded")
                ->descriptionIcon('heroicon-m-document-check')
                ->color('info'),

            Stat::make('Pending Grading', number_format($pendingSubmissions))
                ->description('Submissions awaiting review')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Certificates Issued', number_format($totalCertificates))
                ->description("{$certificatesThisMonth} issued this month")
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success'),

            Stat::make('Weekly Reports', number_format($totalReports))
                ->description("{$submittedReports} submitted • {$thisWeekReports} this week")
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),
        ];
    }
}
