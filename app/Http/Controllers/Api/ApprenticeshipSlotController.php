<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApprenticeshipSlot;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class ApprenticeshipSlotController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/career/apprenticeships",
     *     tags={"Career Pathways"},
     *     summary="Browse open apprenticeship slots (filterable by state, sector, and required course)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="state", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="lga", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="sector", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="required_course_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="show_all", in="query", required=false, @OA\Schema(type="boolean", default=false), description="If false (default), only slots whose required course the learner has completed (or that require no course) are shown"),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Paginated apprenticeship slots from approved organisations")
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = ApprenticeshipSlot::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('application_deadline')->orWhere('application_deadline', '>=', now()->toDateString());
            })
            ->whereHas('organisation', fn ($q) => $q->where('is_approved', true))
            ->with(['organisation:id,name,state,lga', 'requiredCourse:id,title']);

        if ($state = $request->query('state')) {
            $query->where('state', $state);
        }

        if ($lga = $request->query('lga')) {
            $query->where('lga', $lga);
        }

        if ($sector = $request->query('sector')) {
            $query->where('sector', $sector);
        }

        if ($requiredCourseId = $request->query('required_course_id')) {
            $query->where('required_course_id', $requiredCourseId);
        }

        $showAll = filter_var($request->query('show_all', false), FILTER_VALIDATE_BOOLEAN);

        if (!$showAll) {
            $completedCourseIds = Enrollment::where('user_id', $user->id)
                ->where('status', 'completed')
                ->pluck('course_id');

            $query->where(function ($q) use ($completedCourseIds) {
                $q->whereNull('required_course_id')->orWhereIn('required_course_id', $completedCourseIds);
            });
        }

        $slots = $query->withCount(['apprenticeships as applications_count'])
            ->latest()
            ->paginate($request->query('per_page', 15));

        $appliedSlotIds = $user->apprenticeships()->pluck('apprenticeship_slot_id')->all();

        $slots->getCollection()->transform(function ($slot) use ($appliedSlotIds) {
            $slot->has_applied = in_array($slot->id, $appliedSlotIds, true);
            return $slot;
        });

        return response()->json([
            'success' => true,
            'data' => $slots->items(),
            'pagination' => [
                'current_page' => $slots->currentPage(),
                'last_page' => $slots->lastPage(),
                'per_page' => $slots->perPage(),
                'total' => $slots->total(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/career/apprenticeships/{slot}",
     *     tags={"Career Pathways"},
     *     summary="Get a single apprenticeship slot's detail",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="slot", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Slot detail")
     * )
     */
    public function show(Request $request, ApprenticeshipSlot $slot)
    {
        $slot->load(['organisation:id,name,description,state,lga,website', 'requiredCourse:id,title']);
        $slot->has_applied = $request->user()->apprenticeships()->where('apprenticeship_slot_id', $slot->id)->exists();

        return response()->json(['success' => true, 'data' => $slot]);
    }

    private function ownedSlotOrFail(Request $request, ApprenticeshipSlot $slot)
    {
        $organisation = $request->user()->organisation;

        if (!$organisation || $slot->organisation_id !== $organisation->id) {
            abort(response()->json(['success' => false, 'message' => 'You do not manage this slot'], 403));
        }

        return $organisation;
    }

    /**
     * @OA\Get(
     *     path="/api/org/slots",
     *     tags={"Career Pathways"},
     *     summary="List the authenticated organisation's own apprenticeship slots",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="Organisation's slots with applicant counts")
     * )
     */
    public function mine(Request $request)
    {
        $organisation = $request->user()->organisation;

        if (!$organisation) {
            return response()->json(['success' => false, 'message' => 'No organisation profile found for this account'], 404);
        }

        $slots = $organisation->slots()
            ->withCount(['apprenticeships as applications_count'])
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $slots]);
    }

    /**
     * @OA\Post(
     *     path="/api/org/slots",
     *     tags={"Career Pathways"},
     *     summary="Post a new apprenticeship slot (organisation must be admin-approved first)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","description","state","duration"},
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="sector", type="string", nullable=true),
     *             @OA\Property(property="state", type="string"),
     *             @OA\Property(property="lga", type="string", nullable=true),
     *             @OA\Property(property="duration", type="string", example="3 months"),
     *             @OA\Property(property="required_course_id", type="integer", nullable=true),
     *             @OA\Property(property="openings", type="integer", default=1),
     *             @OA\Property(property="application_deadline", type="string", format="date", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Slot created"),
     *     @OA\Response(response=403, description="Organisation is not yet approved by an admin")
     * )
     */
    public function store(Request $request)
    {
        $organisation = $request->user()->organisation;

        if (!$organisation) {
            return response()->json(['success' => false, 'message' => 'No organisation profile found for this account'], 404);
        }

        if (!$organisation->is_approved) {
            return response()->json(['success' => false, 'message' => 'Your organisation must be approved by an admin before you can post slots'], 403);
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'sector' => 'nullable|string|max:255',
            'state' => 'required|string|max:255',
            'lga' => 'nullable|string|max:255',
            'duration' => 'required|string|max:255',
            'required_course_id' => 'nullable|exists:courses,id',
            'openings' => 'nullable|integer|min:1',
            'application_deadline' => 'nullable|date',
        ]);

        $slot = $organisation->slots()->create($data);
        // openings/is_active fall back to DB-level defaults when omitted, which
        // the in-memory instance from create() doesn't know about — reload so
        // the response reflects what was actually persisted.
        $slot->refresh();

        return response()->json(['success' => true, 'data' => $slot, 'message' => 'Slot posted successfully'], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/org/slots/{slot}",
     *     tags={"Career Pathways"},
     *     summary="Update an apprenticeship slot the organisation owns",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="slot", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Slot updated"),
     *     @OA\Response(response=403, description="Not the owning organisation")
     * )
     */
    public function update(Request $request, ApprenticeshipSlot $slot)
    {
        $this->ownedSlotOrFail($request, $slot);

        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'sector' => 'nullable|string|max:255',
            'state' => 'sometimes|required|string|max:255',
            'lga' => 'nullable|string|max:255',
            'duration' => 'sometimes|required|string|max:255',
            'required_course_id' => 'nullable|exists:courses,id',
            'openings' => 'nullable|integer|min:1',
            'application_deadline' => 'nullable|date',
        ]);

        $slot->update($data);

        return response()->json(['success' => true, 'data' => $slot, 'message' => 'Slot updated']);
    }

    /**
     * @OA\Post(
     *     path="/api/org/slots/{slot}/close",
     *     tags={"Career Pathways"},
     *     summary="Close an apprenticeship slot so it no longer appears in learner browsing",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="slot", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Slot closed"),
     *     @OA\Response(response=403, description="Not the owning organisation")
     * )
     */
    public function close(Request $request, ApprenticeshipSlot $slot)
    {
        $this->ownedSlotOrFail($request, $slot);

        $slot->update(['is_active' => false]);

        return response()->json(['success' => true, 'data' => $slot, 'message' => 'Slot closed']);
    }
}
