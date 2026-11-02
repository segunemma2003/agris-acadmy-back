<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseOutcomeSurvey;
use App\Models\Enrollment;
use App\Support\ParticipantProfile;
use Illuminate\Http\Request;

/**
 * Part 5 — end-of-course check. Asked when a learner completes a course
 * ("completion") and again six months after completing it ("six_month").
 */
class CourseOutcomeSurveyController extends Controller
{
    /** Checks this learner still owes, oldest first. */
    public function pending(Request $request)
    {
        $user = $request->user();
        $enrollments = Enrollment::with(['course:id,title,slug', 'outcomeSurveys:id,enrollment_id,stage'])
            ->where('user_id', $user->id)
            ->where(fn ($q) => $q->where('status', 'completed')->orWhere('progress_percentage', '>=', 100))
            ->orderBy('completed_at')
            ->get();

        $pending = [];
        foreach ($enrollments as $enrollment) {
            $done = $enrollment->outcomeSurveys->pluck('stage')->all();
            $completedAt = $enrollment->completed_at ?? $enrollment->updated_at;
            $stage = null;
            if (! in_array('completion', $done, true)) {
                $stage = 'completion';
            } elseif (! in_array('six_month', $done, true) && $completedAt && $completedAt->lte(now()->subMonths(6))) {
                $stage = 'six_month';
            }
            if ($stage) {
                $pending[] = [
                    'enrollment_id' => $enrollment->id,
                    'stage' => $stage,
                    'course' => $enrollment->course ? ['id' => $enrollment->course->id, 'title' => $enrollment->course->title] : null,
                    'completed_course' => $enrollment->status === 'completed' || (float) $enrollment->progress_percentage >= 100,
                ];
            }
        }

        return response()->json(['success' => true, 'data' => $pending]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'enrollment_id' => 'required|integer',
            'stage' => 'required|' . ParticipantProfile::in(ParticipantProfile::SURVEY_STAGES),
            'completed_course' => 'required|boolean',
            'work_change' => 'required|' . ParticipantProfile::in(ParticipantProfile::WORK_CHANGE),
            'started_enterprise' => 'required|boolean',
            'feels_respected' => 'nullable|' . ParticipantProfile::in(ParticipantProfile::YES_NO_PREFER),
            'income_band' => 'required|' . ParticipantProfile::in(ParticipantProfile::INCOME),
            'rating' => 'required|' . ParticipantProfile::in(ParticipantProfile::RATING),
            'used_learning' => 'required|' . ParticipantProfile::in(ParticipantProfile::USED_LEARNING),
            'would_recommend' => 'required|boolean',
            'improvement' => 'nullable|string|max:2000',
            'declaration_name' => 'required|string|max:255',
            'declaration' => 'accepted',
        ]);

        $enrollment = Enrollment::where('id', $data['enrollment_id'])->where('user_id', $user->id)->first();
        if (! $enrollment) {
            return response()->json(['success' => false, 'message' => 'Enrollment not found'], 404);
        }

        $survey = CourseOutcomeSurvey::updateOrCreate(
            ['enrollment_id' => $enrollment->id, 'stage' => $data['stage']],
            [
                ...collect($data)->except(['enrollment_id', 'stage', 'declaration'])->all(),
                // 5.5 only applies when work changed
                'feels_respected' => $data['work_change'] === 'same' ? null : ($data['feels_respected'] ?? null),
                'user_id' => $user->id,
                'course_id' => $enrollment->course_id,
                'declaration_date' => now()->toDateString(),
            ]
        );

        return response()->json(['success' => true, 'message' => 'Thank you — your answers have been saved.', 'data' => $survey]);
    }
}
