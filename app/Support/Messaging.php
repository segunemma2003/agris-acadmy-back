<?php

namespace App\Support;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Who may message whom.
 *
 *  - Learner ↔ course tutor (learner enrolled in the course)
 *  - Learner ↔ facilitator in the same location (course_id 0, or within a course)
 *  - Learner ↔ learner when both are enrolled in the same course ("classmates")
 *  - Admins may message anyone
 */
class Messaging
{
    public static function canMessage(User $from, User $to, ?Course $course): bool
    {
        if ($from->id === $to->id || ! $to->is_active) {
            return false;
        }
        if ($from->role === 'admin') {
            return true;
        }

        if (self::sameLocationFacilitatorPair($from, $to)) {
            return true;
        }
        if (! $course) {
            return false;
        }

        $fromEnrolled = self::isEnrolled($from, $course);
        $toEnrolled = self::isEnrolled($to, $course);

        return ($fromEnrolled && self::isTutor($to, $course))
            || ($toEnrolled && self::isTutor($from, $course))
            || ($fromEnrolled && $toEnrolled && $from->role === 'student' && $to->role === 'student');
    }

    public static function isEnrolled(User $user, Course $course): bool
    {
        return Enrollment::where('user_id', $user->id)->where('course_id', $course->id)
            ->where('status', '!=', 'cancelled')->exists();
    }

    public static function isTutor(User $user, Course $course): bool
    {
        return $course->tutor_id === $user->id || $course->tutors()->where('tutor_id', $user->id)->exists();
    }

    /** A learner and a facilitator who work in the same location. */
    public static function sameLocationFacilitatorPair(User $a, User $b): bool
    {
        $roles = [$a->role, $b->role];
        if (! in_array('facilitator', $roles, true) || ! in_array('student', $roles, true)) {
            return false;
        }
        $la = strtolower(trim((string) $a->location));
        $lb = strtolower(trim((string) $b->location));

        return $la !== '' && $la === $lb;
    }

    /**
     * People this user can start a conversation with, grouped by course, plus
     * facilitators in their location. `$search` filters names (classmate lists can be long).
     *
     * @return array{courses: list<array>, facilitators: list<array>}
     */
    public static function contacts(User $user, ?string $search = null, int $perCourse = 50): array
    {
        $person = fn (User $u, string $kind) => ['id' => $u->id, 'name' => $u->name, 'avatar' => $u->avatar, 'kind' => $kind];
        $nameFilter = fn ($q) => $search ? $q->where('name', 'like', '%' . $search . '%') : $q;

        // Courses the user learns in, or teaches
        $learning = Enrollment::where('user_id', $user->id)->where('status', '!=', 'cancelled')->pluck('course_id');
        $teaching = Course::where('tutor_id', $user->id)->pluck('id')
            ->merge(DB::table('course_tutors')->where('tutor_id', $user->id)->pluck('course_id'));

        $courses = Course::whereIn('id', $learning->merge($teaching)->unique())
            ->with(['tutor:id,name,avatar,is_active', 'tutors:id,name,avatar,is_active'])
            ->orderBy('title')->get();

        $groups = $courses->map(function (Course $course) use ($user, $person, $nameFilter, $perCourse, $teaching, $search) {
            $iTeach = $teaching->contains($course->id);
            $tutors = collect([$course->tutor])->merge($course->tutors)->filter()
                ->unique('id')->reject(fn ($t) => $t->id === $user->id || ! $t->is_active)
                ->filter(fn ($t) => ! $search || stripos($t->name, $search) !== false)
                ->map(fn ($t) => $person($t, 'tutor'))->values();

            // Learners: classmates for a learner, the class list for a tutor
            $learners = $user->role === 'student' || $iTeach
                ? User::query()
                    ->whereIn('id', Enrollment::where('course_id', $course->id)->where('status', '!=', 'cancelled')->select('user_id'))
                    ->where('role', 'student')->where('is_active', true)->where('id', '!=', $user->id)
                    ->tap($nameFilter)->orderBy('name')->limit($perCourse)->get()
                    ->map(fn ($u) => $person($u, $iTeach ? 'learner' : 'classmate'))->values()
                : collect();

            return [
                'course_id' => $course->id,
                'course_title' => $course->title,
                'tutors' => $iTeach ? [] : $tutors->all(),
                'learners' => $learners->all(),
            ];
        })->filter(fn ($g) => $g['tutors'] || $g['learners'])->values()->all();

        $facilitators = [];
        $loc = strtolower(trim((string) $user->location));
        if ($user->role === 'student' && $loc !== '') {
            $facilitators = User::where('role', 'facilitator')->where('is_active', true)
                ->whereRaw('LOWER(TRIM(location)) = ?', [$loc])->tap($nameFilter)->orderBy('name')->get()
                ->map(fn ($u) => $person($u, 'facilitator'))->all();
        }

        return ['courses' => $groups, 'facilitators' => $facilitators];
    }
}
