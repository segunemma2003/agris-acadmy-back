<?php

namespace App\Filament\Tutor\Widgets;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Message;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class TutorStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $tutorId = Auth::id();

        // Tutors can view all courses
        $totalCourses = Course::count();
        $publishedCourses = Course::where('is_published', true)->count();
        
        // Count students from all courses
        $totalStudents = Enrollment::distinct('user_id')->count('user_id');
        $activeEnrollments = Enrollment::where('status', 'active')->count();

        // Count assignments from all courses
        $pendingAssignments = AssignmentSubmission::where('status', 'pending')->count();
        $totalAssignments = Assignment::count();

        $unreadMessages = Message::where('recipient_id', $tutorId)
            ->where('is_read', false)
            ->count();

        return [
            Stat::make('Total Courses', $totalCourses)
                ->description("{$publishedCourses} published")
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary')
                ->chart([1, 2, 3, 4, 5, 6, $totalCourses]),

            Stat::make('Total Students', $totalStudents)
                ->description("{$activeEnrollments} active enrollments")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success')
                ->chart([5, 10, 15, 20, 25, 30, $totalStudents]),

            Stat::make('Pending Assignments', $pendingAssignments)
                ->description("{$totalAssignments} total assignments")
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),

            Stat::make('Unread Messages', $unreadMessages)
                ->description('From students')
                ->descriptionIcon('heroicon-m-envelope')
                ->color('danger'),
        ];
    }
}

