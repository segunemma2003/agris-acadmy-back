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
        $courseIds = Course::query()->accessibleByTutor($tutorId)->pluck('id');

        $totalCourses = $courseIds->count();
        $publishedCourses = Course::query()
            ->accessibleByTutor($tutorId)
            ->where('is_published', true)
            ->count();

        $totalStudents = Enrollment::query()
            ->whereIn('course_id', $courseIds)
            ->distinct('user_id')
            ->count('user_id');
        $activeEnrollments = Enrollment::query()
            ->whereIn('course_id', $courseIds)
            ->where('status', 'active')
            ->count();

        $pendingAssignments = AssignmentSubmission::query()
            ->whereHas('assignment', fn ($q) => $q->whereIn('course_id', $courseIds))
            ->where('status', 'pending')
            ->count();
        $totalAssignments = Assignment::query()
            ->whereIn('course_id', $courseIds)
            ->count();

        $unreadMessages = Message::where('recipient_id', $tutorId)
            ->where('is_read', false)
            ->count();

        return [
            Stat::make('My Courses', $totalCourses)
                ->description("{$publishedCourses} published")
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary')
                ->chart([1, 2, 3, 4, 5, 6, $totalCourses]),

            Stat::make('My Students', $totalStudents)
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
