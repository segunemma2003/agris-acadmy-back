<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\TestAttempt;
use App\Models\User;

/**
 * Learn → Fund: resolve Academy learners for Agrisiti Finance by email or phone.
 */
class LearnerCompletionService
{
    /**
     * Find a learner by email (exact, case-insensitive) or phone (NG variants).
     */
    public function findUser(?string $email = null, ?string $phone = null): ?User
    {
        $email = $email !== null ? trim($email) : null;
        $phone = $phone !== null ? trim($phone) : null;

        if ($email) {
            $user = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();
            if ($user) {
                return $user;
            }
        }

        if ($phone) {
            return $this->findUserByPhone($phone);
        }

        return null;
    }

    /**
     * Full school profile for Finance admin review + graduate verification.
     *
     * @return array{found: bool, user: ?array, completions: array, enrollments: array, certificates: array}
     */
    public function lookup(?string $email = null, ?string $phone = null): array
    {
        $user = $this->findUser($email, $phone);

        if (! $user) {
            return [
                'found' => false,
                'user' => null,
                'completions' => [],
                'enrollments' => [],
                'certificates' => [],
                'profile_summary' => [],
                'quiz_average_score' => null,
            ];
        }

        $user->loadMissing([
            'enrollments.course.category:id,name,slug',
            'certificates.course.category:id,name,slug',
            'certificates.enrollment:id,status,progress_percentage,completed_at',
        ]);

        $certificates = $user->certificates
            ->sortByDesc(fn (Certificate $c) => $c->issued_date?->timestamp ?? $c->created_at?->timestamp ?? 0)
            ->values();

        $quizByCourse = $this->quizAveragesByCourse((int) $user->id);

        $completions = $certificates
            ->map(function (Certificate $certificate) use ($quizByCourse) {
                $row = $this->mapCompletion($certificate);
                $courseId = $certificate->course_id;
                $row['quiz_average_score'] = $courseId && isset($quizByCourse[$courseId])
                    ? $quizByCourse[$courseId]
                    : null;

                return $row;
            })
            ->values()
            ->all();

        $enrollments = $user->enrollments
            ->sortByDesc('updated_at')
            ->values()
            ->map(function ($enrollment) use ($quizByCourse) {
                $courseId = $enrollment->course_id;

                return [
                    'id' => $enrollment->id,
                    'status' => $enrollment->status,
                    'progress_percentage' => (float) ($enrollment->progress_percentage ?? 0),
                    'enrolled_at' => optional($enrollment->enrolled_at)?->toIso8601String(),
                    'completed_at' => optional($enrollment->completed_at)?->toIso8601String(),
                    'course' => $this->mapCourse($enrollment->course),
                    'quiz_average_score' => $courseId && isset($quizByCourse[$courseId])
                        ? $quizByCourse[$courseId]
                        : null,
                ];
            })
            ->all();

        $overallQuiz = $this->overallQuizAverage((int) $user->id);

        $profileSummary = collect($completions)
            ->filter(fn ($row) => ($row['completed'] ?? false) === true)
            ->map(fn ($row) => [
                'course_name' => $row['course']['title'] ?? null,
                'course_track' => $row['course']['track']['name'] ?? null,
                'completion_date' => $row['completion_date'] ?? null,
                'certificate_id' => $row['certificate']['id'] ?? null,
                'certificate_number' => $row['certificate']['certificate_number'] ?? null,
                'quiz_average_score' => $row['quiz_average_score'] ?? null,
            ])
            ->values()
            ->all();

        // If no certificate yet but completed enrollments exist, surface those too
        if ($profileSummary === []) {
            $profileSummary = collect($enrollments)
                ->filter(fn ($row) => ($row['status'] ?? '') === 'completed')
                ->map(fn ($row) => [
                    'course_name' => $row['course']['title'] ?? null,
                    'course_track' => $row['course']['track']['name'] ?? null,
                    'completion_date' => isset($row['completed_at'])
                        ? substr((string) $row['completed_at'], 0, 10)
                        : null,
                    'certificate_id' => null,
                    'certificate_number' => null,
                    'quiz_average_score' => $row['quiz_average_score'] ?? null,
                ])
                ->values()
                ->all();
        }

        return [
            'found' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'state' => $user->state,
                'lga' => $user->lga,
                'occupation' => $user->occupation,
            ],
            'completions' => $completions,
            'enrollments' => $enrollments,
            'certificates' => $certificates->map(fn (Certificate $c) => $this->mapCertificate($c))->all(),
            'has_completed_course' => collect($completions)->contains(fn ($row) => ($row['completed'] ?? false) === true)
                || collect($enrollments)->contains(fn ($row) => ($row['status'] ?? '') === 'completed'),
            'primary_completion' => collect($completions)->first(fn ($row) => ($row['completed'] ?? false) === true)
                ?? ($completions[0] ?? null),
            'profile_summary' => $profileSummary,
            'quiz_average_score' => $overallQuiz,
        ];
    }

    /**
     * Slim payload for automatic graduate verification at Finance register.
     */
    public function completionStatus(?string $email = null, ?string $phone = null): array
    {
        $profile = $this->lookup($email, $phone);
        $primary = $profile['primary_completion'] ?? null;

        return [
            'found' => $profile['found'],
            'completed' => (bool) ($profile['has_completed_course'] ?? false),
            'user' => $profile['user'],
            'completion_status' => $primary['completed'] ?? false,
            'certificate_id' => $primary['certificate']['id'] ?? null,
            'certificate_number' => $primary['certificate']['certificate_number'] ?? null,
            'course_track' => $primary['course']['track']['name']
                ?? $primary['course']['title']
                ?? null,
            'course_title' => $primary['course']['title'] ?? null,
            'completion_date' => $primary['completion_date'] ?? null,
            'completions' => $profile['completions'],
        ];
    }

    /**
     * Look up a certificate by number for manual borrower entry / admin review.
     */
    public function lookupByCertificateNumber(string $code): array
    {
        $code = strtoupper(trim($code));
        $certificate = Certificate::with([
            'user:id,name,email,phone,state,lga,occupation',
            'course.category:id,name,slug',
            'enrollment:id,status,progress_percentage,completed_at',
        ])->where('certificate_number', $code)->first();

        if (! $certificate) {
            return [
                'found' => false,
                'valid' => false,
                'completion' => null,
            ];
        }

        return [
            'found' => true,
            'valid' => true,
            'user' => $certificate->user ? [
                'id' => $certificate->user->id,
                'name' => $certificate->user->name,
                'email' => $certificate->user->email,
                'phone' => $certificate->user->phone,
            ] : null,
            'completion' => $this->mapCompletion($certificate),
        ];
    }

    private function mapCompletion(Certificate $certificate): array
    {
        $enrollment = $certificate->enrollment;
        $completed = filled($certificate->file_path) && (
            ! $enrollment
            || $enrollment->status === 'completed'
            || (float) ($enrollment->progress_percentage ?? 0) >= 100
        );

        $completionDate = optional($enrollment?->completed_at)?->toDateString()
            ?? optional($certificate->issued_date)?->toDateString();

        return [
            'completed' => $completed,
            'enrollment_status' => $enrollment?->status,
            'progress_percentage' => (float) ($enrollment?->progress_percentage ?? ($completed ? 100 : 0)),
            'completion_date' => $completionDate,
            'course' => $this->mapCourse($certificate->course),
            'certificate' => $this->mapCertificate($certificate),
        ];
    }

    private function mapCertificate(Certificate $certificate): array
    {
        return [
            'id' => $certificate->id,
            'certificate_number' => $certificate->certificate_number,
            'recipient_name' => $certificate->recipient_name,
            'issued_date' => optional($certificate->issued_date)?->toDateString(),
            'has_pdf' => filled($certificate->file_path),
            'file_path' => $certificate->file_path,
            'funding_eligible' => filled($certificate->file_path),
        ];
    }

    private function mapCourse($course): ?array
    {
        if (! $course) {
            return null;
        }

        $category = $course->relationLoaded('category') ? $course->category : null;

        return [
            'id' => $course->id,
            'title' => $course->title,
            'slug' => $course->slug,
            'track' => $category ? [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ] : null,
        ];
    }

    /**
     * Best attempt % per module quiz, then averaged per course.
     *
     * @return array<int, float> course_id => average percentage
     */
    private function quizAveragesByCourse(int $userId): array
    {
        $bestPerTest = TestAttempt::query()
            ->selectRaw('module_test_id, MAX(percentage) as best_pct')
            ->where('user_id', $userId)
            ->whereNotNull('completed_at')
            ->groupBy('module_test_id')
            ->get();

        if ($bestPerTest->isEmpty()) {
            return [];
        }

        $testIds = $bestPerTest->pluck('module_test_id')->all();
        $courseByTest = \App\Models\ModuleTest::query()
            ->whereIn('id', $testIds)
            ->pluck('course_id', 'id');

        $bucket = [];
        foreach ($bestPerTest as $row) {
            $courseId = (int) ($courseByTest[$row->module_test_id] ?? 0);
            if ($courseId <= 0) {
                continue;
            }
            $bucket[$courseId][] = (float) $row->best_pct;
        }

        $averages = [];
        foreach ($bucket as $courseId => $scores) {
            $averages[$courseId] = round(array_sum($scores) / max(1, count($scores)), 1);
        }

        return $averages;
    }

    private function overallQuizAverage(int $userId): ?float
    {
        $bestPerTest = TestAttempt::query()
            ->selectRaw('module_test_id, MAX(percentage) as best_pct')
            ->where('user_id', $userId)
            ->whereNotNull('completed_at')
            ->groupBy('module_test_id')
            ->pluck('best_pct');

        if ($bestPerTest->isEmpty()) {
            return null;
        }

        return round((float) $bestPerTest->avg(), 1);
    }

    private function findUserByPhone(string $phoneNumber): ?User
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber) ?? '';
        $national = preg_match('/^234(\d{10})$/', $digits, $m) || preg_match('/^0?(\d{10})$/', $digits, $m)
            ? $m[1]
            : $digits;

        if ($national === '') {
            return null;
        }

        return User::whereIn('phone', [$national, "0{$national}", "234{$national}", "+234{$national}"])
            ->orWhere('phone', 'like', "%{$national}")
            ->first();
    }
}
