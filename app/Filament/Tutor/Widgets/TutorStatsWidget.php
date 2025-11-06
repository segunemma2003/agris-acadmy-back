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

        $myCourses = Course::where('tutor_id', $tutorId)->count();
        $publishedCourses = Course::where('tutor_id', $tutorId)->where('is_published', true)->count();
        $totalStudents = Enrollment::whereHas('course', function ($query) use ($tutorId) {
            $query->where('tutor_id', $tutorId);
        })->distinct('user_id')->count('user_id');

        $activeEnrollments = Enrollment::whereHas('course', function ($query) use ($tutorId) {
            $query->where('tutor_id', $tutorId);
        })->where('status', 'active')->count();

        $pendingAssignments = AssignmentSubmission::whereHas('assignment.course', function ($query) use ($tutorId) {
            $query->where('tutor_id', $tutorId);
        })->where('status', 'pending')->count();

        $unreadMessages = Message::where('recipient_id', $tutorId)
            ->where('is_read', false)
            ->count();

        $totalAssignments = Assignment::whereHas('course', function ($query) use ($tutorId) {
            $query->where('tutor_id', $tutorId);
        })->count();

        return [
            Stat::make('My Courses', $myCourses)
                ->description("{$publishedCourses} published")
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary')
                ->chart([1, 2, 3, 4, 5, 6, $myCourses]),

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

