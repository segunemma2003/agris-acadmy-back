<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseVrCompletion;
use App\Models\CourseVrContent;
use App\Models\CourseVrSimAttempt;
use App\Models\Enrollment;
use App\Services\VrSceneGeneratorService;
use App\Services\VrStudioService;
use Illuminate\Http\Request;
use RuntimeException;

class VrStudioController extends Controller
{
    public function __construct(
        private VrStudioService $studio,
        private VrSceneGeneratorService $generator,
    ) {
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

    /**
     * Courses (with their modules) this author can add a VR experience to —
     * used when a tutor adds a ready-made simulation from the Studio.
     */
    public function courses(Request $request)
    {
        $user = $request->user();
        if (! $user || ! in_array($user->role, ['admin', 'tutor', 'facilitator'], true)) {
            return response()->json(['message' => 'Only tutors, facilitators, and admins can author VR.'], 403);
        }

        $courses = $this->studio->authorCoursesQuery($user)
            ->with(['modules:id,course_id,title'])
            ->orderBy('title')
            ->limit(200)
            ->get(['id', 'title'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'title' => $c->title,
                'modules' => $c->modules->map(fn ($m) => ['id' => $m->id, 'title' => $m->title])->values(),
            ]);

        return response()->json(['data' => $courses]);
    }

    /**
     * Create a VR experience in one of the author's courses, optionally with a
     * scene (e.g. a ready-made simulation lesson) and published straight away.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (! $user || ! in_array($user->role, ['admin', 'tutor', 'facilitator'], true)) {
            return response()->json(['message' => 'Only tutors, facilitators, and admins can author VR.'], 403);
        }

        $data = $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'module_id' => 'nullable|integer|exists:modules,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'cta_label' => 'nullable|string|max:100',
            'scene' => 'nullable|array',
            'duration_minutes' => 'nullable|integer|min:0',
            'publish' => 'sometimes|boolean',
        ]);

        if (! $this->studio->authorCoursesQuery($user)->whereKey($data['course_id'])->exists()) {
            return response()->json(['message' => 'You cannot add content to this course.'], 403);
        }
        if (! empty($data['module_id']) && ! \App\Models\Module::whereKey($data['module_id'])->where('course_id', $data['course_id'])->exists()) {
            return response()->json(['message' => 'That module is not part of the course.'], 422);
        }

        $experience = CourseVrContent::create(array_filter([
            'course_id' => $data['course_id'],
            'module_id' => $data['module_id'] ?? null,
            'tutor_id' => $user->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'instructions' => $data['instructions'] ?? null,
            'cta_label' => $data['cta_label'] ?? 'Launch VR',
            'scene_json' => $data['scene'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'studio_status' => 'draft',
            'is_active' => true,
        ], fn ($v) => $v !== null)); // let column defaults apply (e.g. duration_minutes)

        if ($request->boolean('publish')) {
            if (empty($experience->scene_json)) {
                $experience->scene_json = $this->studio->defaultScene($experience);
                $experience->save();
            }
            $experience = $this->studio->publish($experience);
        }

        return response()->json([
            'message' => $request->boolean('publish') ? 'Added to the course and published' : 'Added to the course as a draft',
            'data' => $this->studio->serializeExperience($experience->fresh(['course', 'module'])),
        ], 201);
    }

    /**
     * The signed-in learner's simulation results across all their courses
     * (best score, passed or not, attempts), for the Academy dashboard.
     */
    public function myResults(Request $request)
    {
        $user = $request->user();
        $rows = CourseVrSimAttempt::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get(['course_vr_content_id', 'course_id', 'passed', 'score', 'created_at'])
            ->groupBy('course_vr_content_id');
        if ($rows->isEmpty()) {
            return response()->json(['data' => []]);
        }
        $items = CourseVrContent::query()
            ->with('course:id,title')
            ->whereIn('id', $rows->keys())
            ->get()
            ->keyBy('id');

        $data = $rows->map(function ($attempts, $id) use ($items) {
            $vr = $items->get($id);
            if (! $vr) {
                return null;
            }
            $last = $attempts->first();

            return [
                'id' => $vr->id,
                'title' => $vr->title,
                'course_id' => $vr->course_id,
                'course_title' => $vr->course?->title,
                'best_score' => (int) $attempts->max('score'),
                'last_score' => (int) $last->score,
                'passed' => $attempts->contains('passed', true),
                'attempts' => $attempts->count(),
                'last_attempt_at' => $last->created_at?->toIso8601String(),
                'play_url' => $vr->vr_url,
            ];
        })->filter()->sortByDesc('last_attempt_at')->values();

        return response()->json(['data' => $data]);
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
     * Generate scene + copy from an author prompt (Claude). Does not auto-save/publish.
     */
    public function generate(Request $request, CourseVrContent $experience)
    {
        $user = $request->user();
        if (! $this->studio->userCanAuthor($user, $experience)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'prompt' => 'required|string|min:8|max:4000',
        ]);

        try {
            $generated = $this->generator->generate(
                $experience->loadMissing(['course:id,title']),
                $data['prompt']
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Scene generated',
            'data' => $generated,
        ]);
    }

    /**
     * Upload a panorama / image asset for this experience (S3 when configured, else public disk).
     */
    public function uploadAsset(Request $request, CourseVrContent $experience)
    {
        $user = $request->user();
        if (! $this->studio->userCanAuthor($user, $experience)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,webp|max:20480',
        ]);

        try {
            $stored = $this->studio->storeAsset($experience, $request->file('file'));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Asset uploaded',
            'data' => $stored,
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
            ->first();
        if (! $experience) {
            return response()->json(['message' => "We couldn't find this VR experience. It may not be published yet, or the link may be old — ask your tutor for the latest link."], 404);
        }

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
    /**
     * Save a finished simulation attempt (fish pond lesson, ...).
     * Enrolled learners: stored and, when passed, the VR item is marked complete.
     * Authors previewing their own lesson get the score back but nothing is stored.
     */
    public function storeAttempt(Request $request, CourseVrContent $experience)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $data = $request->validate([
            'lesson_id' => 'nullable|string|max:100',
            'mode' => 'required|in:simple,3d',
            'outcome' => 'required|array',
            'outcome.passed' => 'required|boolean',
            'outcome.score' => 'required|integer|min:0|max:100',
            'outcome.correctAnswers' => 'nullable|integer|min:0',
            'outcome.totalQuestions' => 'nullable|integer|min:0',
            'actions' => 'nullable|array|max:2000',
            'answers' => 'nullable|array|max:200',
            'summary' => 'nullable|array',
        ]);

        $enrolled = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $experience->course_id)
            ->exists();

        if (! $enrolled) {
            if ($this->studio->userCanAuthor($user, $experience)) {
                return response()->json(['message' => 'Preview attempt (not saved)', 'data' => ['saved' => false]]);
            }

            return response()->json(['message' => 'Not enrolled'], 403);
        }

        $attempt = CourseVrSimAttempt::create([
            'user_id' => $user->id,
            'course_vr_content_id' => $experience->id,
            'course_id' => $experience->course_id,
            'lesson_id' => $data['lesson_id'] ?? null,
            'mode' => $data['mode'],
            'passed' => (bool) $data['outcome']['passed'],
            'score' => (int) $data['outcome']['score'],
            'correct_answers' => (int) ($data['outcome']['correctAnswers'] ?? 0),
            'total_questions' => (int) ($data['outcome']['totalQuestions'] ?? 0),
            'outcome' => $data['outcome'],
            'actions' => $data['actions'] ?? [],
            'answers' => $data['answers'] ?? [],
            'summary' => $data['summary'] ?? null,
        ]);

        if ($attempt->passed) {
            CourseVrCompletion::query()->updateOrCreate(
                ['user_id' => $user->id, 'course_vr_content_id' => $experience->id],
                ['course_id' => $experience->course_id, 'completed_at' => now()]
            );
        }

        $best = CourseVrSimAttempt::query()
            ->where('user_id', $user->id)
            ->where('course_vr_content_id', $experience->id)
            ->max('score');

        return response()->json([
            'message' => 'Attempt saved',
            'data' => [
                'saved' => true,
                'attempt_id' => $attempt->id,
                'passed' => $attempt->passed,
                'score' => $attempt->score,
                'best_score' => (int) $best,
            ],
        ]);
    }

    /**
     * Results for the tutor: every learner attempt at this simulation plus a
     * class summary (pass rate, average score, questions most often missed).
     */
    public function attempts(Request $request, CourseVrContent $experience)
    {
        $user = $request->user();
        if (! $this->studio->userCanAuthor($user, $experience)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $rows = CourseVrSimAttempt::query()
            ->where('course_vr_content_id', $experience->id)
            ->with('user:id,name,email')
            ->latest()
            ->limit(500)
            ->get();

        $missed = [];
        foreach ($rows as $row) {
            foreach ((array) $row->answers as $answer) {
                if (empty($answer['correct']) && ! empty($answer['questionId'])) {
                    $missed[$answer['questionId']] = ($missed[$answer['questionId']] ?? 0) + 1;
                }
            }
        }
        arsort($missed);

        $learners = $rows->pluck('user_id')->unique()->count();

        return response()->json([
            'data' => [
                'summary' => [
                    'attempts' => $rows->count(),
                    'learners' => $learners,
                    'pass_rate' => $rows->count() ? round(100 * $rows->where('passed', true)->count() / $rows->count()) : null,
                    'average_score' => $rows->count() ? round($rows->avg('score')) : null,
                    'most_missed_questions' => $missed,
                ],
                'attempts' => $rows->map(fn (CourseVrSimAttempt $a) => [
                    'id' => $a->id,
                    'learner' => $a->user?->name ?? 'Learner #'.$a->user_id,
                    'mode' => $a->mode,
                    'passed' => $a->passed,
                    'score' => $a->score,
                    'correct_answers' => $a->correct_answers,
                    'total_questions' => $a->total_questions,
                    'reasons' => $a->outcome['reasons'] ?? [],
                    'actions_count' => count((array) $a->actions),
                    'summary' => $a->summary,
                    'created_at' => $a->created_at?->toIso8601String(),
                ])->values(),
            ],
        ]);
    }
}
