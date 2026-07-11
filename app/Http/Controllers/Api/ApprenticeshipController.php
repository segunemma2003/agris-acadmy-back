<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Apprenticeship;
use App\Models\ApprenticeshipSlot;
use App\Models\Certificate;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class ApprenticeshipController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/career/apprenticeships/{slot}/express-interest",
     *     tags={"Career Pathways"},
     *     summary="Express interest in an apprenticeship slot; the learner's certificate for the required course (if any) is attached automatically",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="slot", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=201, description="Interest recorded"),
     *     @OA\Response(response=400, description="Already applied, slot closed, or required course not completed")
     * )
     */
    public function expressInterest(Request $request, ApprenticeshipSlot $slot)
    {
        $user = $request->user();

        if (!$slot->is_active) {
            return response()->json(['success' => false, 'message' => 'This slot is no longer open'], 400);
        }

        $existing = Apprenticeship::where('apprenticeship_slot_id', $slot->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return response()->json(['success' => false, 'message' => 'You have already expressed interest in this slot'], 400);
        }

        $certificateId = null;

        if ($slot->required_course_id) {
            $hasCompleted = $user->enrollments()
                ->where('course_id', $slot->required_course_id)
                ->where('status', 'completed')
                ->exists();

            if (!$hasCompleted) {
                return response()->json(['success' => false, 'message' => 'You must complete the required course before applying'], 400);
            }

            $certificateId = Certificate::where('user_id', $user->id)
                ->where('course_id', $slot->required_course_id)
                ->value('id');
        }

        $apprenticeship = Apprenticeship::create([
            'apprenticeship_slot_id' => $slot->id,
            'user_id' => $user->id,
            'certificate_id' => $certificateId,
            'status' => 'interested',
        ]);

        return response()->json([
            'success' => true,
            'data' => $apprenticeship->load('slot.organisation'),
            'message' => 'Interest expressed successfully',
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/career/my-applications",
     *     tags={"Career Pathways"},
     *     summary="List the authenticated learner's apprenticeship applications and their status",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="Learner's applications")
     * )
     */
    public function myApplications(Request $request)
    {
        $applications = $request->user()->apprenticeships()
            ->with(['slot.organisation:id,name,state,lga'])
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $applications]);
    }

    /**
     * @OA\Get(
     *     path="/api/org/slots/{slot}/applicants",
     *     tags={"Career Pathways"},
     *     summary="List applicants for a slot the organisation owns, each with their attached certificate",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="slot", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Applicants list"),
     *     @OA\Response(response=403, description="Not the owning organisation")
     * )
     */
    public function applicants(Request $request, ApprenticeshipSlot $slot)
    {
        $organisation = $request->user()->organisation;

        if (!$organisation || $slot->organisation_id !== $organisation->id) {
            return response()->json(['success' => false, 'message' => 'You do not manage this slot'], 403);
        }

        $applicants = $slot->apprenticeships()
            ->with(['user:id,name,email,phone,state,lga', 'certificate'])
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $applicants]);
    }

    /**
     * @OA\Get(
     *     path="/api/org/interns",
     *     tags={"Career Pathways"},
     *     summary="List all apprenticeship applications across the organisation's slots, for stats and the My Interns view",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="All applications for the authenticated organisation")
     * )
     */
    public function interns(Request $request)
    {
        $organisation = $request->user()->organisation;

        if (!$organisation) {
            return response()->json(['success' => false, 'message' => 'No organisation profile found'], 403);
        }

        $applications = Apprenticeship::whereHas('slot', function ($query) use ($organisation) {
            $query->where('organisation_id', $organisation->id);
        })
            ->with(['user:id,name,email,phone,state,lga', 'certificate', 'slot:id,title'])
            ->withCount('logs')
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $applications]);
    }

    /**
     * @OA\Post(
     *     path="/api/org/applications/{apprenticeship}/review",
     *     tags={"Career Pathways"},
     *     summary="Accept or reject a learner's application; the learner is notified of the outcome",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="apprenticeship", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(required={"status"}, @OA\Property(property="status", type="string", enum={"accepted","rejected"}))),
     *     @OA\Response(response=200, description="Application reviewed"),
     *     @OA\Response(response=403, description="Not the owning organisation")
     * )
     */
    public function review(Request $request, Apprenticeship $apprenticeship)
    {
        $organisation = $request->user()->organisation;

        if (!$organisation || $apprenticeship->slot->organisation_id !== $organisation->id) {
            return response()->json(['success' => false, 'message' => 'You do not manage this application'], 403);
        }

        $request->validate([
            'status' => 'required|in:accepted,rejected',
        ]);

        $apprenticeship->update([
            'status' => $request->status,
            'reviewed_at' => now(),
        ]);

        return response()->json(['success' => true, 'data' => $apprenticeship, 'message' => 'Application ' . $request->status]);
    }
}
