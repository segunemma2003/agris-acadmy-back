<?php

namespace App\Filament\Facilitator\Widgets;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Message;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class FacilitatorStatsWidget extends BaseWidget
{
    // Not shown yet: under its old filename (Supervisor*Widget.php) Filament's
    // discovery never registered this widget. Set to true to add it to the
    // facilitator dashboard.
    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $facilitatorId = Auth::id();

        $myCourses = Course::where('tutor_id', $facilitatorId)->count();
        $publishedCourses = Course::where('tutor_id', $facilitatorId)->where('is_published', true)->count();
        $totalStudents = Enrollment::whereHas('course', function ($query) use ($facilitatorId) {
            $query->where('tutor_id', $facilitatorId);
        })->distinct('user_id')->count('user_id');

        $activeEnrollments = Enrollment::whereHas('course', function ($query) use ($facilitatorId) {
            $query->where('tutor_id', $facilitatorId);
        })->where('status', 'active')->count();

        $pendingAssignments = AssignmentSubmission::whereHas('assignment.course', function ($query) use ($facilitatorId) {
            $query->where('tutor_id', $facilitatorId);
        })->where('status', 'pending')->count();

        $unreadMessages = Message::where('recipient_id', $facilitatorId)
            ->where('is_read', false)
            ->count();

        $totalAssignments = Assignment::whereHas('course', function ($query) use ($facilitatorId) {
            $query->where('tutor_id', $facilitatorId);
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

