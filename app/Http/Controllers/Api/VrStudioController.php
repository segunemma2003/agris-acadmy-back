<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseVrCompletion;
use App\Models\CourseVrContent;
use App\Models\Enrollment;
use App\Services\VrStudioService;
use Illuminate\Http\Request;

class VrStudioController extends Controller
{
    public function __construct(private VrStudioService $studio)
    {
    }

    /**
     * Exchange Filament handoff token for a Studio session (Sanctum token + experience).
     */
    public function exchange(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string|min:20',
        ]);

        $session = $this->studio->exchangeHandoff($data['token']);
        if (! $session) {
            return response()->json(['message' => 'Invalid or expired Studio handoff token.'], 401);
        }

        return response()->json([
            'message' => 'Studio session started',
            'data' => $session,
        ]);
    }

    /**
     * List VR experiences this author can edit (admin / tutor / facilitator).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user || ! in_array($user->role, ['admin', 'tutor', 'facilitator'], true)) {
            return response()->json(['message' => 'Only tutors, facilitators, and admins can author VR.'], 403);
        }

        $query = CourseVrContent::query()->with(['course:id,title', 'module:id,title'])->orderByDesc('updated_at');

        if ($user->role === 'tutor') {
            $query->whereHas('course', fn ($q) => $q->accessibleByTutor($user->id));
        } elseif ($user->role === 'facilitator') {
            $location = $user->location;
            if (! $location) {
                return response()->json(['data' => []]);
            }
            $query->whereHas(
                'course.enrollments.user',
                fn ($q) => $q->whereRaw('LOWER(location) = LOWER(?)', [$location])
            );
        }

        $items = $query->limit(100)->get()->map(fn (CourseVrContent $row) => $this->studio->serializeExperience($row));

        return response()->json([
            'data' => $items,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    public function show(Request $request, CourseVrContent $experience)
    {
        $user = $request->user();
        if (! $this->studio->userCanAuthor($user, $experience)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'data' => $this->studio->serializeExperience($experience->load(['course', 'module'])),
        ]);
    }

    public function update(Request $request, CourseVrContent $experience)
    {
        $user = $request->user();
        if (! $this->studio->userCanAuthor($user, $experience)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'cta_label' => 'nullable|string|max:100',
            'module_id' => 'nullable|integer|exists:modules,id',
            'scene' => 'sometimes|array',
            'duration_minutes' => 'nullable|integer|min:0',
        ]);

        if (array_key_exists('title', $data)) {
            $experience->title = $data['title'];
        }
        if (array_key_exists('description', $data)) {
            $experience->description = $data['description'];
        }
        if (array_key_exists('instructions', $data)) {
            $experience->instructions = $data['instructions'];
        }
        if (array_key_exists('cta_label', $data)) {
            $experience->cta_label = $data['cta_label'];
        }
        if (array_key_exists('module_id', $data)) {
            $experience->module_id = $data['module_id'];
        }
        if (array_key_exists('duration_minutes', $data)) {
            $experience->duration_minutes = $data['duration_minutes'];
        }
        if (array_key_exists('scene', $data)) {
            $experience->scene_json = $data['scene'];
            if ($experience->studio_status === 'published') {
                // Edits to published content stay published but URL remains stable.
                $experience->vr_url = $this->studio->playerUrlFor($experience);
            } else {
                $experience->studio_status = 'draft';
            }
        }

        $experience->save();

        return response()->json([
            'message' => 'Experience saved',
            'data' => $this->studio->serializeExperience($experience->fresh(['course', 'module'])),
        ]);
    }

    public function publish(Request $request, CourseVrContent $experience)
    {
        $user = $request->user();
        if (! $this->studio->userCanAuthor($user, $experience)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (empty($experience->scene_json)) {
            $experience->scene_json = $this->studio->defaultScene($experience);
            $experience->save();
        }

        $published = $this->studio->publish($experience);

        return response()->json([
            'message' => 'Experience published',
            'data' => $this->studio->serializeExperience($published),
        ]);
    }

    /**
     * Public player payload (learners). Enrollment required when authenticated.
     */
    public function play(Request $request, string $slug)
    {
        $experience = CourseVrContent::query()
            ->where('studio_slug', $slug)
            ->where('studio_status', 'published')
            ->where('is_active', true)
            ->with(['course:id,title'])
            ->firstOrFail();

        $user = $request->user('sanctum');
        if (! $user && $request->filled('token')) {
            $plain = $request->query('token');
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($plain);
            $user = $accessToken?->tokenable;
        }

        if ($user) {
            $enrolled = Enrollment::query()
                ->where('user_id', $user->id)
                ->where('course_id', $experience->course_id)
                ->exists();
            if (! $enrolled && ! in_array($user->role, ['admin', 'tutor', 'facilitator'], true)) {
                return response()->json(['message' => 'You must be enrolled to launch this VR experience.'], 403);
            }
        }

        return response()->json([
            'data' => [
                'id' => $experience->id,
                'title' => $experience->title,
                'instructions' => $experience->instructions,
                'cta_label' => $experience->cta_label ?: 'Continue',
                'course_id' => $experience->course_id,
                'course_title' => $experience->course?->title,
                'scene' => $experience->scene_json ?: $this->studio->defaultScene($experience),
            ],
        ]);
    }

    /**
     * Optional completion callback — marks a lightweight progress note for the course.
     * Full topic progress stays Filament/LMS-owned; this records VR activity completion.
     */
    public function complete(Request $request, CourseVrContent $experience)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $enrolled = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $experience->course_id)
            ->exists();

        if (! $enrolled) {
            return response()->json(['message' => 'Not enrolled'], 403);
        }

        $completion = CourseVrCompletion::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'course_vr_content_id' => $experience->id,
            ],
            [
                'course_id' => $experience->course_id,
                'completed_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'VR experience marked complete',
            'data' => [
                'vr_content_id' => $experience->id,
                'course_id' => $experience->course_id,
                'user_id' => $user->id,
                'completed_at' => $completion->completed_at?->toIso8601String(),
            ],
        ]);
    }
}
