<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Apprenticeship;
use App\Models\ApprenticeshipLog;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class ApprenticeshipLogController extends Controller
{
    /**
     * Whether this user may log/view activity for this apprenticeship: the
     * apprentice themself, their host organisation, or an admin.
     */
    private function canAccess(Request $request, Apprenticeship $apprenticeship): bool
    {
        $user = $request->user();

        if ($apprenticeship->user_id === $user->id) {
            return true;
        }

        if ($user->role === 'admin') {
            return true;
        }

        $organisation = $user->organisation;

        return $organisation && $apprenticeship->slot->organisation_id === $organisation->id;
    }

    /**
     * @OA\Post(
     *     path="/api/apprenticeships/{apprenticeship}/logs",
     *     tags={"Career Pathways"},
     *     summary="Log today's attendance/activity for an active apprenticeship (learner or host organisation)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="apprenticeship", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="log_date", type="string", format="date", nullable=true, description="Defaults to today"),
     *             @OA\Property(property="attended", type="boolean", default=true),
     *             @OA\Property(property="activity_description", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Log recorded (or updated, if one already existed for that date)"),
     *     @OA\Response(response=403, description="Not the apprentice, host organisation, or an admin"),
     *     @OA\Response(response=400, description="Apprenticeship is not in an active (accepted) state")
     * )
     */
    public function store(Request $request, Apprenticeship $apprenticeship)
    {
        if (!$this->canAccess($request, $apprenticeship)) {
            return response()->json(['success' => false, 'message' => 'You cannot log activity for this apprenticeship'], 403);
        }

        if ($apprenticeship->status !== 'accepted') {
            return response()->json(['success' => false, 'message' => 'Only an active (accepted) apprenticeship can be logged'], 400);
        }

        $data = $request->validate([
            'log_date' => 'nullable|date',
            'attended' => 'nullable|boolean',
            'activity_description' => 'nullable|string|max:2000',
        ]);

        $log = ApprenticeshipLog::updateOrCreate(
            [
                'apprenticeship_id' => $apprenticeship->id,
                'log_date' => $data['log_date'] ?? now()->toDateString(),
            ],
            [
                'attended' => $data['attended'] ?? true,
                'activity_description' => $data['activity_description'] ?? null,
                'source' => 'web',
            ]
        );

        return response()->json(['success' => true, 'data' => $log, 'message' => 'Log recorded'], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/apprenticeships/{apprenticeship}/logs",
     *     tags={"Career Pathways"},
     *     summary="Full log history for an apprenticeship as a timeline, with days since acceptance that have no log flagged",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="apprenticeship", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Timeline: logged days plus 'no log submitted' placeholders for missed days",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="date", type="string", format="date"),
     *                 @OA\Property(property="has_log", type="boolean"),
     *                 @OA\Property(property="attended", type="boolean", nullable=true),
     *                 @OA\Property(property="activity_description", type="string", nullable=true),
     *                 @OA\Property(property="source", type="string", nullable=true)
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=403, description="Not the apprentice, host organisation, or an admin")
     * )
     */
    public function index(Request $request, Apprenticeship $apprenticeship)
    {
        if (!$this->canAccess($request, $apprenticeship)) {
            return response()->json(['success' => false, 'message' => 'You cannot view logs for this apprenticeship'], 403);
        }

        $logsByDate = $apprenticeship->logs()->get()->keyBy(fn ($log) => $log->log_date->toDateString());

        $start = ($apprenticeship->reviewed_at ?? $apprenticeship->created_at)->copy()->startOfDay();
        $end = now()->startOfDay();

        // Cap how far back the timeline reconstructs missing-day placeholders
        // for, so a very long-running or long-past placement can't blow up
        // the response with a year's worth of daily rows.
        $earliestAllowed = $end->copy()->subDays(180);
        if ($start->lt($earliestAllowed)) {
            $start = $earliestAllowed;
        }

        $timeline = [];
        for ($date = $end->copy(); $date->gte($start); $date->subDay()) {
            $key = $date->toDateString();
            $log = $logsByDate->get($key);

            $timeline[] = [
                'date' => $key,
                'has_log' => (bool) $log,
                'attended' => $log?->attended,
                'activity_description' => $log?->activity_description,
                'source' => $log?->source,
            ];
        }

        return response()->json(['success' => true, 'data' => $timeline]);
    }
}
