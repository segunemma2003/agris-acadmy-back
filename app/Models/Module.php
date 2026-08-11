<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $fillable = [
        'course_id',
        'tutor_id',
        'title',
        'title_ha',
        'description',
        'description_ha',
        'is_translated_ha',
        'total_topics',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_translated_ha' => 'boolean',
        ];
    }

    // Relationships
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class)->orderBy('sort_order');
    }

    public function test(): HasMany
    {
        return $this->hasMany(ModuleTest::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    // Update total_topics when topics are added/removed
    // Send email notifications when a new module is added
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($module) {
            $module->updateTotalTopics();
        });

        // Auto-translate to Hausa in the background whenever the source text changes
        static::saved(function ($module) {
            if ($module->wasChanged('title') || $module->wasChanged('description') || $module->wasRecentlyCreated) {
                \App\Jobs\TranslateContentJob::dispatch(static::class, $module->id, [
                    'title' => 'title_ha',
                    'description' => 'description_ha',
                ]);
            }
        });

        // Send email notification when a new module is created and is active
        static::created(function ($module) {
            if ($module->is_active) {
                $module->notifyEnrolledStudents();
            }
        });

        // Send email notification when a module is activated
        static::updated(function ($module) {
            if ($module->is_active && $module->wasChanged('is_active') && !$module->getOriginal('is_active')) {
                $module->notifyEnrolledStudents();
            }
        });
    }

    /**
     * Notify all enrolled students about the new module
     */
    public function notifyEnrolledStudents(): void
    {
        try {
            $course = $this->course;
            if (!$course) {
                return;
            }

            // Get all active enrollments for this course
            $enrollments = \App\Models\Enrollment::where('course_id', $course->id)
                ->where('status', 'active')
                ->with('user')
                ->get();

            foreach ($enrollments as $enrollment) {
                if ($enrollment->user) {
                    // Create in-app notification
                    try {
                        \App\Services\NotificationService::create(
                            $enrollment->user,
                            'module_added',
                            'New Module Added',
                            "A new module '{$this->title}' has been added to '{$course->title}'. Check it out!",
                            'module',
                            $this->id,
                            [
                                'course_id' => $course->id,
                                'course_title' => $course->title,
                                'course_slug' => $course->slug,
                                'module_id' => $this->id,
                                'module_title' => $this->title,
                            ]
                        );
                    } catch (\Exception $e) {
                        \Log::error('Failed to create module notification', [
                            'user_id' => $enrollment->user->id,
                            'module_id' => $this->id,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    // Send email notification when the learner opted in for this type
                    if (
                        $enrollment->user->email
                        && \App\Support\NotificationPreferences::allowsEmail(
                            $enrollment->user->notification_preferences,
                            'module_added'
                        )
                    ) {
                        try {
                            \Illuminate\Support\Facades\Mail::to($enrollment->user->email)
                                ->queue(new \App\Mail\NewModuleNotificationMail(
                                    $enrollment->user,
                                    $course,
                                    $this
                                ));
                        } catch (\Exception $e) {
                            \Log::error('Failed to queue new module notification email', [
                                'user_id' => $enrollment->user->id,
                                'module_id' => $this->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error('Failed to notify enrolled students about new module', [
                'module_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function updateTotalTopics(): void
    {
        $this->total_topics = $this->topics()->count();
        $this->saveQuietly();
    }

    /**
     * The module immediately before this one in the same course.
     */
    public function previousModule(): ?self
    {
        return static::where('course_id', $this->course_id)
            ->where('is_active', true)
            ->where('sort_order', '<', $this->sort_order)
            ->orderByDesc('sort_order')
            ->first();
    }

    public function activeTest(): ?ModuleTest
    {
        return $this->test()->where('is_active', true)->first();
    }

    /** Product rule: learners need at least 80% to unlock the next module. */
    public const DEFAULT_PASSING_PERCENTAGE = 80.0;

    public function effectivePassingScore(?ModuleTest $test = null): float
    {
        $test ??= $this->activeTest();
        if (! $test) {
            return self::DEFAULT_PASSING_PERCENTAGE;
        }

        return max((float) $test->passing_score, self::DEFAULT_PASSING_PERCENTAGE);
    }

    /**
     * Whether this module is gated behind passing the previous module's quiz,
     * and if so, the learner's best attempt so far. A module with no previous
     * module, or whose previous module has no active quiz, is never locked.
     */
    public function lockStatusFor(?User $user): array
    {
        $previousModule = $this->previousModule();

        if (! $previousModule) {
            return [
                'locked' => false,
                'quiz_passed' => true,
                'has_attempted' => false,
                'required_percentage' => self::DEFAULT_PASSING_PERCENTAGE,
                'best_percentage' => 0.0,
                'message' => null,
                'previous_module' => null,
            ];
        }

        $previousTest = $previousModule->activeTest();

        if (! $previousTest) {
            return [
                'locked' => false,
                'quiz_passed' => true,
                'has_attempted' => false,
                'required_percentage' => self::DEFAULT_PASSING_PERCENTAGE,
                'best_percentage' => 0.0,
                'message' => null,
                'previous_module' => [
                    'id' => $previousModule->id,
                    'title' => $previousModule->title,
                ],
            ];
        }

        $required = $previousModule->effectivePassingScore($previousTest);

        $bestAttempt = $user
            ? TestAttempt::where('module_test_id', $previousTest->id)
                ->where('user_id', $user->id)
                ->orderByDesc('percentage')
                ->first()
            : null;

        $bestPercentage = $bestAttempt ? (float) $bestAttempt->percentage : 0.0;
        $passed = $bestAttempt ? $bestPercentage >= $required : false;
        $hasAttempted = (bool) $bestAttempt;

        $message = null;
        if (! $passed) {
            if ($hasAttempted) {
                $message = sprintf(
                    'You scored %.0f%%. You need %.0f%% to unlock the next module.',
                    $bestPercentage,
                    $required
                );
            } else {
                $message = sprintf(
                    'Complete and pass the quiz for "%s" with at least %.0f%% to unlock this module.',
                    $previousModule->title,
                    $required
                );
            }
        }

        return [
            'locked' => ! $passed,
            'quiz_passed' => $passed,
            'has_attempted' => $hasAttempted,
            'required_percentage' => $required,
            'best_percentage' => $bestPercentage,
            'message' => $message,
            'previous_module' => [
                'id' => $previousModule->id,
                'title' => $previousModule->title,
            ],
        ];
    }

    /**
     * Status of THIS module's own quiz (used to decide if the next module unlocks).
     */
    public function quizStatusFor(?User $user): array
    {
        $test = $this->activeTest();

        if (! $test) {
            return [
                'has_quiz' => false,
                'quiz_completed' => true,
                'quiz_passed' => true,
                'has_attempted' => false,
                'required_percentage' => self::DEFAULT_PASSING_PERCENTAGE,
                'best_percentage' => 0.0,
                'message' => null,
            ];
        }

        $required = $this->effectivePassingScore($test);

        $bestAttempt = $user
            ? TestAttempt::where('module_test_id', $test->id)
                ->where('user_id', $user->id)
                ->orderByDesc('percentage')
                ->first()
            : null;

        $bestPercentage = $bestAttempt ? (float) $bestAttempt->percentage : 0.0;
        $passed = $bestAttempt ? $bestPercentage >= $required : false;
        $hasAttempted = (bool) $bestAttempt;

        $message = null;
        if ($hasAttempted && ! $passed) {
            $message = sprintf(
                'You scored %.0f%%. You need %.0f%% to unlock the next module.',
                $bestPercentage,
                $required
            );
        }

        return [
            'has_quiz' => true,
            'quiz_completed' => $hasAttempted,
            'quiz_passed' => $passed,
            'has_attempted' => $hasAttempted,
            'required_percentage' => $required,
            'best_percentage' => $bestPercentage,
            'message' => $message,
        ];
    }
}
