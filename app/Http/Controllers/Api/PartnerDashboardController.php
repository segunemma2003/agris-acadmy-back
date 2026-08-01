<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Apprenticeship;
use App\Models\ApprenticeshipLog;
use App\Models\ApprenticeshipSlot;
use App\Models\Category;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Module;
use App\Models\Organisation;
use App\Models\StudentProgress;
use App\Models\TestAttempt;
use App\Models\Topic;
use App\Models\TopicTestAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

class PartnerDashboardController extends Controller
{
    private const TREND_MONTHS = 6;

    /**
     * @OA\Get(
     *     path="/api/partner/permissions",
     *     tags={"Partner Dashboard"},
     *     summary="Which dashboard sections the authenticated partner (funder) account is allowed to see",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="Granted sections and their labels"),
     *     @OA\Response(response=403, description="Not a partner account")
     * )
     */
    public function permissions(Request $request)
    {
        $partner = $this->resolvePartner($request);

        if ($partner instanceof \Illuminate\Http\JsonResponse) {
            return $partner;
        }

        $granted = $partner->dashboard_permissions ?? [];
        $catalog = config('partner_dashboard.sections');

        $sections = collect(array_keys($catalog))
            ->filter(fn ($key) => in_array($key, $granted, true))
            ->map(fn ($key) => [
                'key' => $key,
                'label' => $catalog[$key]['label'],
                'description' => $catalog[$key]['description'],
                'icon' => $catalog[$key]['icon'],
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'partner_name' => $partner->name,
                'organisation_name' => $partner->name, // backwards compatible with older clients
                'has_access' => $sections->isNotEmpty(),
                'sections' => $sections,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/partner/dashboard",
     *     tags={"Partner Dashboard"},
     *     summary="Partner dashboard data (stats, breakdowns, 6-month trends), filtered server-side to only the sections the admin has granted this funder",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="Dashboard data for granted sections only"),
     *     @OA\Response(response=403, description="Not a partner account or no sections granted")
     * )
     */
    public function index(Request $request)
    {
        $partner = $this->resolvePartner($request);

        if ($partner instanceof \Illuminate\Http\JsonResponse) {
            return $partner;
        }

        $granted = $partner->dashboard_permissions ?? [];

        if (empty($granted)) {
            return response()->json([
                'success' => false,
                'message' => "Your account doesn't have dashboard access yet. Contact the administrator.",
            ], 403);
        }

        $data = [];
        $orderedSections = array_filter(array_keys(config('partner_dashboard.sections')), fn ($key) => in_array($key, $granted, true));

        foreach ($orderedSections as $section) {
            $data[$section] = match ($section) {
                'platform_overview' => $this->platformOverview(),
                'courses' => $this->coursesSection(),
                'course_performance' => $this->coursePerformanceSection(),
                'learners' => $this->learnersSection(),
                'demographics' => $this->demographicsSection(),
                'geography' => $this->geographySection(),
                'engagement' => $this->engagementSection(),
                'enrollments' => $this->enrollmentsSection(),
                'apprenticeships' => $this->apprenticeshipsSection(),
                'certificates' => $this->certificatesSection(),
                default => null,
            };
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    private function platformOverview(): array
    {
        $topicsCount = Topic::count();
        $videosCount = Topic::whereNotNull('video_url')->where('video_url', '!=', '')->count();
        $modulesCount = Module::count();
        $students = $this->studentDirectory();
        $genderFilters = $this->genderFilters();
        $locationFilters = $this->locationFilters();
        $byAge = $this->ageBreakdown();

        $totalEnrollments = Enrollment::count();
        $activeEnrollments = Enrollment::where('status', 'active')->count();
        $completedEnrollments = Enrollment::where('status', 'completed')->count();
        $freeCourses = Course::where('is_free', true)->count();
        $paidCourses = Course::where('is_free', false)->count();
        $publishedCourses = Course::where('is_published', true)->count();
        $activeStudents = User::where('role', 'student')->where('is_active', true)->count();
        $placedInterns = Apprenticeship::whereIn('status', ['accepted', 'completed'])->count();
        $hostCompanies = Organisation::count();

        $breakdowns = collect([
            ['title' => 'Students by Gender', 'items' => $genderFilters->map(fn ($row) => [
                'label' => $row['label'],
                'value' => $row['value'],
            ])->values()],
            ['title' => 'Students by Location', 'items' => $locationFilters->take(10)->map(fn ($row) => [
                'label' => $row['label'],
                'value' => $row['value'],
            ])->values()],
            $byAge->isNotEmpty() ? ['title' => 'Students by Age Group', 'items' => $byAge] : null,
            [
                'title' => 'Courses by Pricing',
                'items' => collect([
                    ['label' => 'Free', 'value' => $freeCourses],
                    ['label' => 'Paid', 'value' => $paidCourses],
                ])->sortByDesc('value')->values(),
            ],
            [
                'title' => 'Enrollments by Status',
                'items' => collect([
                    ['label' => 'Active', 'value' => $activeEnrollments],
                    ['label' => 'Completed', 'value' => $completedEnrollments],
                    ['label' => 'Cancelled', 'value' => Enrollment::where('status', 'cancelled')->count()],
                ])->sortByDesc('value')->values(),
            ],
        ])->filter()->values();

        return [
            'stats' => [
                ['key' => 'total_learners', 'label' => 'Students', 'value' => User::where('role', 'student')->count(), 'unit' => 'count'],
                ['key' => 'active_students', 'label' => 'Active Students', 'value' => $activeStudents, 'unit' => 'count'],
                ['key' => 'total_courses', 'label' => 'Courses', 'value' => Course::count(), 'unit' => 'count'],
                ['key' => 'total_topics', 'label' => 'Topics', 'value' => $topicsCount, 'unit' => 'count'],
                ['key' => 'total_videos', 'label' => 'Videos', 'value' => $videosCount, 'unit' => 'count'],
                ['key' => 'total_enrollments', 'label' => 'Enrollments', 'value' => $totalEnrollments, 'unit' => 'count'],
                ['key' => 'completion_rate', 'label' => 'Completion Rate', 'value' => $totalEnrollments > 0 ? round(($completedEnrollments / $totalEnrollments) * 100, 1) : 0, 'unit' => 'percentage'],
                ['key' => 'certificates_issued', 'label' => 'Certificates', 'value' => Certificate::count(), 'unit' => 'count'],
                ['key' => 'host_companies', 'label' => 'Host Companies', 'value' => $hostCompanies, 'unit' => 'count'],
                ['key' => 'placed_interns', 'label' => 'Placed / Employed', 'value' => $placedInterns, 'unit' => 'count'],
                ['key' => 'total_tutors', 'label' => 'Tutors', 'value' => User::where('role', 'tutor')->count(), 'unit' => 'count'],
                ['key' => 'total_facilitators', 'label' => 'Facilitators', 'value' => User::where('role', 'facilitator')->count(), 'unit' => 'count'],
                ['key' => 'published_courses', 'label' => 'Published Courses', 'value' => $publishedCourses, 'unit' => 'count'],
                ['key' => 'free_courses', 'label' => 'Free Courses', 'value' => $freeCourses, 'unit' => 'count'],
                ['key' => 'paid_courses', 'label' => 'Paid Courses', 'value' => $paidCourses, 'unit' => 'count'],
                ['key' => 'total_modules', 'label' => 'Modules', 'value' => $modulesCount, 'unit' => 'count'],
                ['key' => 'states_represented', 'label' => 'Locations', 'value' => $locationFilters->where('key', '!=', 'unspecified')->count(), 'unit' => 'count'],
            ],
            'highlight_stats' => [
                ['key' => 'total_learners', 'label' => 'Students', 'value' => User::where('role', 'student')->count(), 'unit' => 'count'],
                ['key' => 'total_courses', 'label' => 'Courses', 'value' => Course::count(), 'unit' => 'count'],
                ['key' => 'total_enrollments', 'label' => 'Enrollments', 'value' => $totalEnrollments, 'unit' => 'count'],
                ['key' => 'total_topics', 'label' => 'Topics', 'value' => $topicsCount, 'unit' => 'count'],
                ['key' => 'total_videos', 'label' => 'Videos', 'value' => $videosCount, 'unit' => 'count'],
                ['key' => 'placed_interns', 'label' => 'Employed Interns', 'value' => $placedInterns, 'unit' => 'count'],
            ],
            'breakdowns' => $breakdowns,
            'gender_filters' => $genderFilters,
            'location_filters' => $locationFilters,
            // Students retained for client-side chart filters only (overview UI never renders a table).
            'students' => $students,
            'trend' => [
                'title' => 'New Student Signups',
                'unit' => 'count',
                'points' => $this->monthlyTrend(User::where('role', 'student')),
            ],
        ];
    }

    private function coursesSection(): array
    {
        $byCategory = Category::withCount('courses')
            ->where('is_active', true)
            ->orderByDesc('courses_count')
            ->get(['name'])
            ->map(fn ($c) => ['label' => $c->name, 'value' => $c->courses_count])
            ->filter(fn ($row) => $row['value'] > 0)
            ->values();

        $catalog = $this->courseCatalog();
        $topicsCount = Topic::count();
        $videosCount = Topic::whereNotNull('video_url')->where('video_url', '!=', '')->count();
        $modulesCount = Module::count();

        return [
            'stats' => [
                ['key' => 'total_courses', 'label' => 'Total Courses', 'value' => Course::count(), 'unit' => 'count'],
                ['key' => 'published_courses', 'label' => 'Published', 'value' => Course::where('is_published', true)->count(), 'unit' => 'count'],
                ['key' => 'free_courses', 'label' => 'Free', 'value' => Course::where('is_free', true)->count(), 'unit' => 'count'],
                ['key' => 'paid_courses', 'label' => 'Paid', 'value' => Course::where('is_free', false)->count(), 'unit' => 'count'],
                ['key' => 'total_modules', 'label' => 'Modules', 'value' => $modulesCount, 'unit' => 'count'],
                ['key' => 'total_topics', 'label' => 'Topics', 'value' => $topicsCount, 'unit' => 'count'],
                ['key' => 'total_videos', 'label' => 'Videos', 'value' => $videosCount, 'unit' => 'count'],
                ['key' => 'total_enrollments', 'label' => 'Enrollments', 'value' => (int) Course::sum('enrollment_count'), 'unit' => 'count'],
            ],
            'breakdowns' => [
                ['title' => 'Courses by Category', 'items' => $byCategory],
            ],
            'catalog' => $catalog,
        ];
    }

    private function coursePerformanceSection(): array
    {
        $topCourses = Course::orderByDesc('enrollment_count')
            ->limit(8)
            ->get(['title', 'enrollment_count'])
            ->filter(fn ($c) => $c->enrollment_count > 0)
            ->map(fn ($c) => ['label' => $c->title, 'value' => $c->enrollment_count])
            ->values();

        $avgRating = (float) (Course::where('rating_count', '>', 0)->avg('rating') ?? 0);
        $totalReviews = (int) Course::sum('rating_count');
        $featured = Course::where('is_featured', true)->count();

        return [
            'stats' => [
                ['key' => 'avg_rating', 'label' => 'Average Rating', 'value' => round($avgRating, 1), 'unit' => 'decimal'],
                ['key' => 'total_reviews', 'label' => 'Total Reviews', 'value' => $totalReviews, 'unit' => 'count'],
                ['key' => 'featured_courses', 'label' => 'Featured', 'value' => $featured, 'unit' => 'count'],
                ['key' => 'top_enrollment', 'label' => 'Top Course Enrollments', 'value' => (int) (Course::max('enrollment_count') ?? 0), 'unit' => 'count'],
            ],
            'breakdowns' => [
                ['title' => 'Top Courses by Enrollment', 'items' => $topCourses],
            ],
            'catalog' => $this->courseCatalog(),
        ];
    }

    private function learnersSection(): array
    {
        $totalStudents = User::where('role', 'student')->count();
        $activeLearners = User::where('role', 'student')->where('is_active', true)->count();
        $totalTutors = User::where('role', 'tutor')->count();
        $totalFacilitators = User::where('role', 'facilitator')->count();
        $genderFilters = $this->genderFilters();
        $locationFilters = $this->locationFilters();
        $students = $this->studentDirectory();

        return [
            'stats' => [
                ['key' => 'total_students', 'label' => 'Students', 'value' => $totalStudents, 'unit' => 'count'],
                ['key' => 'active_learners', 'label' => 'Active', 'value' => $activeLearners, 'unit' => 'count'],
                ['key' => 'total_tutors', 'label' => 'Tutors', 'value' => $totalTutors, 'unit' => 'count'],
                ['key' => 'total_facilitators', 'label' => 'Facilitators', 'value' => $totalFacilitators, 'unit' => 'count'],
            ],
            'breakdowns' => [
                ['title' => 'Students by Gender', 'items' => $genderFilters->map(fn ($row) => [
                    'label' => $row['label'],
                    'value' => $row['value'],
                ])->values()],
                ['title' => 'Students by Location', 'items' => $locationFilters->take(8)->map(fn ($row) => [
                    'label' => $row['label'],
                    'value' => $row['value'],
                ])->values()],
            ],
            'gender_filters' => $genderFilters,
            'location_filters' => $locationFilters,
            'students' => $students,
            'trend' => [
                'title' => 'New Student Signups',
                'unit' => 'count',
                'points' => $this->monthlyTrend(User::where('role', 'student')),
            ],
        ];
    }

    private function demographicsSection(): array
    {
        $genderFilters = $this->genderFilters();
        $locationFilters = $this->locationFilters();
        $byAge = $this->ageBreakdown();
        $students = $this->studentDirectory();

        $breakdowns = collect([
            $genderFilters->isNotEmpty() ? ['title' => 'Learners by Gender', 'items' => $genderFilters->map(fn ($row) => [
                'label' => $row['label'],
                'value' => $row['value'],
            ])->values()] : null,
            $byAge->isNotEmpty() ? ['title' => 'Learners by Age Group', 'items' => $byAge] : null,
        ])->filter()->values();

        return [
            'stats' => [
                ['key' => 'total_students', 'label' => 'Students', 'value' => $students->count(), 'unit' => 'count'],
                ['key' => 'profiled_learners', 'label' => 'With Gender', 'value' => User::where('role', 'student')->whereNotNull('gender')->where('gender', '!=', '')->count(), 'unit' => 'count'],
                ['key' => 'with_age', 'label' => 'With Age', 'value' => User::where('role', 'student')->whereNotNull('age')->count(), 'unit' => 'count'],
                ['key' => 'with_location', 'label' => 'With Location', 'value' => User::where('role', 'student')->where(function ($q) {
                    $q->where(function ($inner) {
                        $inner->whereNotNull('state')->where('state', '!=', '');
                    })->orWhere(function ($inner) {
                        $inner->whereNotNull('location')->where('location', '!=', '');
                    });
                })->count(), 'unit' => 'count'],
            ],
            'breakdowns' => $breakdowns,
            'gender_filters' => $genderFilters,
            'location_filters' => $locationFilters,
            'students' => $students,
        ];
    }

    private function geographySection(): array
    {
        $locationFilters = $this->locationFilters();
        $genderFilters = $this->genderFilters();
        $students = $this->studentDirectory();
        $byStateChart = $locationFilters
            ->reject(fn ($row) => $row['key'] === 'unspecified')
            ->take(10)
            ->map(fn ($row) => ['label' => $row['label'], 'value' => $row['value']])
            ->values();

        $statesRepresented = $locationFilters->where('key', '!=', 'unspecified')->count();

        return [
            'stats' => [
                ['key' => 'states_represented', 'label' => 'Locations', 'value' => $statesRepresented, 'unit' => 'count'],
                ['key' => 'students_with_location', 'label' => 'Located Students', 'value' => $locationFilters->where('key', '!=', 'unspecified')->sum('value'), 'unit' => 'count'],
                ['key' => 'unspecified_location', 'label' => 'No Location', 'value' => (int) ($locationFilters->firstWhere('key', 'unspecified')['value'] ?? 0), 'unit' => 'count'],
                ['key' => 'total_students', 'label' => 'All Students', 'value' => $students->count(), 'unit' => 'count'],
            ],
            'breakdowns' => [
                ['title' => 'Learners by State', 'items' => $byStateChart],
            ],
            'gender_filters' => $genderFilters,
            'location_filters' => $locationFilters,
            'students' => $students,
        ];
    }

    private function engagementSection(): array
    {
        $attempts = TestAttempt::select('percentage', 'is_passed')->get()
            ->concat(TopicTestAttempt::select('percentage', 'is_passed')->get());

        $totalAttempts = $attempts->count();
        $avgScore = $totalAttempts > 0 ? round((float) $attempts->avg('percentage'), 1) : 0;
        $passRate = $totalAttempts > 0 ? round($attempts->where('is_passed', true)->count() / $totalAttempts * 100, 1) : 0;
        $passedAttempts = $attempts->where('is_passed', true)->count();

        $avgWatchSeconds = (int) StudentProgress::avg('watch_time_seconds');
        $totalWatchSeconds = (int) StudentProgress::sum('watch_time_seconds');
        $learnersOnStreak = User::where('role', 'student')->where('current_streak', '>', 0)->count();
        $longestStreak = (int) (User::where('role', 'student')->max('longest_streak') ?? 0);
        $progressRows = StudentProgress::count();

        return [
            'stats' => [
                ['key' => 'total_quiz_attempts', 'label' => 'Quiz Attempts', 'value' => $totalAttempts, 'unit' => 'count'],
                ['key' => 'passed_attempts', 'label' => 'Passed Attempts', 'value' => $passedAttempts, 'unit' => 'count'],
                ['key' => 'avg_quiz_score', 'label' => 'Avg Quiz Score', 'value' => $avgScore, 'unit' => 'percentage'],
                ['key' => 'quiz_pass_rate', 'label' => 'Quiz Pass Rate', 'value' => $passRate, 'unit' => 'percentage'],
                ['key' => 'progress_records', 'label' => 'Progress Records', 'value' => $progressRows, 'unit' => 'count'],
                ['key' => 'avg_watch_minutes', 'label' => 'Avg Watch Time (min)', 'value' => intdiv($avgWatchSeconds, 60), 'unit' => 'count'],
                ['key' => 'total_watch_hours', 'label' => 'Total Watch Hours', 'value' => intdiv($totalWatchSeconds, 3600), 'unit' => 'count'],
                ['key' => 'learners_on_streak', 'label' => 'Learners on a Streak', 'value' => $learnersOnStreak, 'unit' => 'count'],
                ['key' => 'longest_streak', 'label' => 'Longest Streak', 'value' => $longestStreak, 'unit' => 'count'],
            ],
            'trend' => [
                'title' => 'Module Quiz Attempts',
                'unit' => 'count',
                'points' => $this->monthlyTrend(TestAttempt::query(), 'completed_at'),
            ],
        ];
    }

    private function enrollmentsSection(): array
    {
        $total = Enrollment::count();
        $active = Enrollment::where('status', 'active')->count();
        $completed = Enrollment::where('status', 'completed')->count();
        $cancelled = Enrollment::where('status', 'cancelled')->count();
        $completionRate = $total > 0 ? round(($completed / $total) * 100, 2) : 0;
        $freeEnrollments = Enrollment::where(function ($q) {
            $q->where('amount_paid', 0)->orWhereNull('amount_paid');
        })->count();
        $paidEnrollments = max(0, $total - $freeEnrollments);

        $list = $this->enrollmentDirectory();

        return [
            'stats' => [
                ['key' => 'total', 'label' => 'Total Enrollments', 'value' => $total, 'unit' => 'count'],
                ['key' => 'active', 'label' => 'Active', 'value' => $active, 'unit' => 'count'],
                ['key' => 'completed', 'label' => 'Completed', 'value' => $completed, 'unit' => 'count'],
                ['key' => 'cancelled', 'label' => 'Cancelled', 'value' => $cancelled, 'unit' => 'count'],
                ['key' => 'free_enrollments', 'label' => 'Free', 'value' => $freeEnrollments, 'unit' => 'count'],
                ['key' => 'paid_enrollments', 'label' => 'Paid', 'value' => $paidEnrollments, 'unit' => 'count'],
                ['key' => 'completion_rate', 'label' => 'Completion Rate', 'value' => $completionRate, 'unit' => 'percentage'],
            ],
            'breakdowns' => [
                [
                    'title' => 'Enrollments by Status',
                    'items' => collect([
                        ['label' => 'Active', 'value' => $active],
                        ['label' => 'Completed', 'value' => $completed],
                        ['label' => 'Cancelled', 'value' => $cancelled],
                    ])->sortByDesc('value')->values(),
                ],
            ],
            'list' => $list,
            'trend' => [
                'title' => 'Enrollments',
                'unit' => 'count',
                'points' => $this->monthlyTrend(Enrollment::query()),
            ],
        ];
    }

    private function apprenticeshipsSection(): array
    {
        $companies = $this->companyDirectory();
        $slots = $this->slotDirectory();
        $placements = $this->placementDirectory();

        $openSlots = ApprenticeshipSlot::where('is_active', true)->count();
        $totalApplicants = Apprenticeship::count();
        $activeInterns = Apprenticeship::where('status', 'accepted')->count();
        $pending = Apprenticeship::where('status', 'interested')->count();
        $rejected = Apprenticeship::where('status', 'rejected')->count();
        $completed = Apprenticeship::where('status', 'completed')->count();
        $employed = $activeInterns + $completed;

        $logsSubmitted = ApprenticeshipLog::count();
        $daysAttended = ApprenticeshipLog::where('attended', true)->count();
        $attendanceRate = $logsSubmitted > 0 ? round(($daysAttended / $logsSubmitted) * 100, 1) : 0;

        return [
            'stats' => [
                ['key' => 'host_companies', 'label' => 'Host Companies', 'value' => $companies->count(), 'unit' => 'count'],
                ['key' => 'platform_open_slots', 'label' => 'Open Slots', 'value' => $openSlots, 'unit' => 'count'],
                ['key' => 'total_slots', 'label' => 'Total Slots', 'value' => ApprenticeshipSlot::count(), 'unit' => 'count'],
                ['key' => 'total_applicants', 'label' => 'Applicants', 'value' => $totalApplicants, 'unit' => 'count'],
                ['key' => 'active_interns', 'label' => 'Active Interns', 'value' => $activeInterns, 'unit' => 'count'],
                ['key' => 'employed', 'label' => 'Placed / Employed', 'value' => $employed, 'unit' => 'count'],
                ['key' => 'completed_placements', 'label' => 'Completed', 'value' => $completed, 'unit' => 'count'],
                ['key' => 'pending_review', 'label' => 'Pending Review', 'value' => $pending, 'unit' => 'count'],
                ['key' => 'logs_submitted', 'label' => 'Daily Logs', 'value' => $logsSubmitted, 'unit' => 'count'],
                ['key' => 'attendance_rate', 'label' => 'Attendance Rate', 'value' => $attendanceRate, 'unit' => 'percentage'],
            ],
            'breakdowns' => [
                [
                    'title' => 'Applications by Status',
                    'items' => collect([
                        ['label' => 'Pending Review', 'value' => $pending],
                        ['label' => 'Accepted (Employed)', 'value' => $activeInterns],
                        ['label' => 'Completed', 'value' => $completed],
                        ['label' => 'Rejected', 'value' => $rejected],
                    ])->sortByDesc('value')->values(),
                ],
                [
                    'title' => 'Companies by Open Slots',
                    'items' => $companies
                        ->sortByDesc('open_slots_count')
                        ->take(8)
                        ->map(fn ($c) => ['label' => $c['name'], 'value' => $c['open_slots_count']])
                        ->values(),
                ],
            ],
            'companies' => $companies,
            'slots' => $slots,
            'placements' => $placements,
            'trend' => [
                'title' => 'New Applicants',
                'unit' => 'count',
                'points' => $this->monthlyTrend(Apprenticeship::query()),
            ],
        ];
    }

    private function certificatesSection(): array
    {
        $list = $this->certificateDirectory();
        $thisMonth = Certificate::where('issued_date', '>=', now()->startOfMonth())->count();

        return [
            'stats' => [
                ['key' => 'total_issued', 'label' => 'Total Issued', 'value' => Certificate::count(), 'unit' => 'count'],
                ['key' => 'issued_this_month', 'label' => 'This Month', 'value' => $thisMonth, 'unit' => 'count'],
                ['key' => 'unique_recipients', 'label' => 'Recipients', 'value' => Certificate::distinct('user_id')->count('user_id'), 'unit' => 'count'],
                ['key' => 'courses_certified', 'label' => 'Courses Certified', 'value' => Certificate::distinct('course_id')->count('course_id'), 'unit' => 'count'],
            ],
            'certificates' => $list,
            'trend' => [
                'title' => 'Certificates Issued',
                'unit' => 'count',
                'points' => $this->monthlyTrend(Certificate::query(), 'issued_date'),
            ],
        ];
    }

    private function courseCatalog()
    {
        return Course::with([
            'category:id,name',
            'tutor:id,name',
            'modules' => fn ($q) => $q->orderBy('sort_order')->with([
                'topics' => fn ($tq) => $tq->orderBy('sort_order')->with([
                    'test' => fn ($testQ) => $testQ->where('is_active', true)->with([
                        'questions' => fn ($qq) => $qq->orderBy('sort_order'),
                    ]),
                ]),
            ]),
        ])
            ->orderByDesc('enrollment_count')
            ->orderBy('title')
            ->get()
            ->map(function (Course $course) {
                $modules = $course->modules->map(function ($module) {
                    $topics = $module->topics->map(function ($topic) {
                        $quizzes = $topic->test->map(function ($test) {
                            $questions = $test->questions->map(fn ($question) => [
                                'id' => $question->id,
                                'question' => $this->plainText($question->question),
                                'question_type' => $question->question_type,
                                'options' => $this->normalizeStringList($question->options ?? []),
                                'explanation' => $this->plainText($question->explanation),
                                'points' => (int) ($question->points ?? 1),
                            ])->values();

                            return [
                                'id' => $test->id,
                                'title' => $this->plainText($test->title) ?: 'Topic quiz',
                                'description' => $this->plainText($test->description),
                                'passing_score' => (int) ($test->passing_score ?? 0),
                                'max_attempts' => (int) ($test->max_attempts ?? 0),
                                'time_limit_minutes' => (int) ($test->time_limit_minutes ?? 0),
                                'total_questions' => $questions->count(),
                                'questions' => $questions,
                            ];
                        })->values();

                        return [
                            'id' => $topic->id,
                            'title' => $topic->title,
                            'description' => $this->plainText($topic->description),
                            'write_up' => $this->plainText($topic->write_up),
                            'has_video' => filled($topic->video_url),
                            'duration_minutes' => (int) ($topic->duration_minutes ?? 0),
                            'is_free' => (bool) $topic->is_free,
                            'is_active' => (bool) $topic->is_active,
                            'content_type' => $topic->content_type,
                            'has_write_up' => filled($topic->write_up),
                            'quizzes' => $quizzes,
                            'quizzes_count' => $quizzes->count(),
                            'questions_count' => $quizzes->sum('total_questions'),
                        ];
                    })->values();

                    return [
                        'id' => $module->id,
                        'title' => $module->title,
                        'description' => $this->plainText($module->description),
                        'topics_count' => $topics->count(),
                        'videos_count' => $topics->where('has_video', true)->count(),
                        'topics' => $topics,
                    ];
                })->values();

                $allTopics = $modules->flatMap(fn ($m) => $m['topics']);

                $requirements = $course->requirements;
                if (is_string($requirements)) {
                    $requirementsText = $this->plainText($requirements);
                    $requirementsList = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $requirementsText ?? '') ?: [])));
                } elseif (is_array($requirements)) {
                    $requirementsList = $this->normalizeStringList($requirements);
                    $requirementsText = implode("\n", $requirementsList) ?: null;
                } else {
                    $requirementsList = [];
                    $requirementsText = null;
                }

                $learn = $this->normalizeStringList($course->what_you_will_learn);
                $get = $this->normalizeStringList($course->what_you_will_get);

                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'category' => $course->category?->name,
                    'tutor' => $course->tutor?->name,
                    'short_description' => $this->plainText($course->short_description),
                    'description' => $this->plainText($course->description),
                    'about' => $this->plainText($course->about),
                    'requirements' => $requirementsText,
                    'requirements_list' => $requirementsList,
                    'what_to_expect' => $this->plainText($course->what_to_expect),
                    'what_you_will_learn' => $learn,
                    'what_you_will_get' => $get,
                    'level' => $course->level,
                    'language' => $course->language,
                    'duration_minutes' => (int) ($course->duration_minutes ?? 0),
                    'is_free' => (bool) $course->is_free,
                    'price' => (float) ($course->price ?? 0),
                    'is_published' => (bool) $course->is_published,
                    'is_featured' => (bool) $course->is_featured,
                    'certificate_included' => (bool) $course->certificate_included,
                    'rating' => (float) ($course->rating ?? 0),
                    'rating_count' => (int) ($course->rating_count ?? 0),
                    'enrollment_count' => (int) $course->enrollment_count,
                    'modules_count' => $modules->count(),
                    'topics_count' => $allTopics->count(),
                    'videos_count' => $allTopics->where('has_video', true)->count(),
                    'modules' => $modules,
                ];
            })
            ->values();
    }

    private function enrollmentDirectory()
    {
        return Enrollment::with([
            'user:id,name,email,gender,state',
            'course:id,title,is_free,price',
        ])
            ->orderByDesc('enrolled_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Enrollment $enrollment) => [
                'id' => $enrollment->id,
                'student_name' => $enrollment->user?->name ?? 'Unknown',
                'student_email' => $enrollment->user?->email,
                'student_gender' => $enrollment->user?->gender
                    ? ucfirst(strtolower(trim($enrollment->user->gender)))
                    : null,
                'student_state' => $enrollment->user?->state,
                'course_title' => $enrollment->course?->title ?? 'Unknown course',
                'course_is_free' => (bool) ($enrollment->course?->is_free),
                'course_price' => (float) ($enrollment->course?->price ?? 0),
                'amount_paid' => (float) ($enrollment->amount_paid ?? 0),
                'status' => $enrollment->status,
                'progress_percentage' => (float) ($enrollment->progress_percentage ?? 0),
                'enrolled_at' => optional($enrollment->enrolled_at ?? $enrollment->created_at)?->toDateString(),
            ])
            ->values();
    }

    private function certificateDirectory()
    {
        return Certificate::with([
            'user:id,name,email,gender,state',
            'course:id,title',
        ])
            ->orderByDesc('issued_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Certificate $certificate) => [
                'id' => $certificate->id,
                'certificate_number' => $certificate->certificate_number,
                'recipient_name' => $certificate->recipient_name ?: ($certificate->user?->name ?? 'Unknown'),
                'student_email' => $certificate->user?->email,
                'student_gender' => $certificate->user?->gender
                    ? ucfirst(strtolower(trim($certificate->user->gender)))
                    : null,
                'student_state' => $certificate->user?->state,
                'course_title' => $certificate->course?->title ?? 'Unknown course',
                'issued_date' => optional($certificate->issued_date)?->toDateString(),
            ])
            ->values();
    }

    private function companyDirectory()
    {
        return Organisation::query()
            ->withCount([
                'slots',
                'slots as open_slots_count' => fn ($q) => $q->where('is_active', true),
            ])
            ->orderBy('name')
            ->get()
            ->map(function (Organisation $org) {
                $slotIds = $org->slots()->pluck('id');
                $placed = $slotIds->isEmpty()
                    ? 0
                    : Apprenticeship::whereIn('apprenticeship_slot_id', $slotIds)
                        ->whereIn('status', ['accepted', 'completed'])
                        ->count();
                $completedCount = $slotIds->isEmpty()
                    ? 0
                    : Apprenticeship::whereIn('apprenticeship_slot_id', $slotIds)
                        ->where('status', 'completed')
                        ->count();

                return [
                    'id' => $org->id,
                    'name' => $org->name,
                    'sector' => $org->sector,
                    'state' => $org->state,
                    'lga' => $org->lga,
                    'website' => $org->website,
                    'is_approved' => (bool) $org->is_approved,
                    'slots_count' => (int) $org->slots_count,
                    'open_slots_count' => (int) $org->open_slots_count,
                    'placed_count' => $placed,
                    'completed_count' => $completedCount,
                ];
            })
            ->filter(fn ($row) => $row['slots_count'] > 0 || $row['is_approved'])
            ->values();
    }

    private function slotDirectory()
    {
        return ApprenticeshipSlot::with([
            'organisation:id,name,sector,state',
            'requiredCourse:id,title',
        ])
            ->withCount([
                'apprenticeships',
                'apprenticeships as interested_count' => fn ($q) => $q->where('status', 'interested'),
                'apprenticeships as accepted_count' => fn ($q) => $q->where('status', 'accepted'),
                'apprenticeships as completed_count' => fn ($q) => $q->where('status', 'completed'),
                'apprenticeships as rejected_count' => fn ($q) => $q->where('status', 'rejected'),
            ])
            ->orderByDesc('is_active')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ApprenticeshipSlot $slot) => [
                'id' => $slot->id,
                'title' => $slot->title,
                'description' => $slot->description,
                'company' => $slot->organisation?->name,
                'company_id' => $slot->organisation_id,
                'sector' => $slot->sector ?: $slot->organisation?->sector,
                'state' => $slot->state,
                'lga' => $slot->lga,
                'duration' => $slot->duration,
                'openings' => (int) ($slot->openings ?? 0),
                'application_deadline' => optional($slot->application_deadline)?->toDateString(),
                'is_active' => (bool) $slot->is_active,
                'required_course' => $slot->requiredCourse?->title,
                'applicants_count' => (int) $slot->apprenticeships_count,
                'interested_count' => (int) $slot->interested_count,
                'accepted_count' => (int) $slot->accepted_count,
                'completed_count' => (int) $slot->completed_count,
                'rejected_count' => (int) $slot->rejected_count,
            ])
            ->values();
    }

    private function placementDirectory()
    {
        return Apprenticeship::with([
            'user:id,name,email,gender,state',
            'slot:id,title,organisation_id,sector,state,duration',
            'slot.organisation:id,name,sector,state',
            'certificate:id,certificate_number',
        ])
            ->withCount([
                'logs',
                'logs as attended_days' => fn ($q) => $q->where('attended', true),
            ])
            ->get()
            ->sortBy(fn (Apprenticeship $row) => match ($row->status) {
                'accepted' => 0,
                'completed' => 1,
                'interested' => 2,
                default => 3,
            })
            ->values()
            ->map(function (Apprenticeship $row) {
                $logs = (int) $row->logs_count;
                $attended = (int) $row->attended_days;

                return [
                    'id' => $row->id,
                    'student_name' => $row->user?->name ?? 'Unknown',
                    'student_email' => $row->user?->email,
                    'student_gender' => $row->user?->gender
                        ? ucfirst(strtolower(trim($row->user->gender)))
                        : null,
                    'student_state' => $row->user?->state,
                    'company' => $row->slot?->organisation?->name,
                    'slot_title' => $row->slot?->title,
                    'sector' => $row->slot?->sector ?: $row->slot?->organisation?->sector,
                    'location' => $row->slot?->state,
                    'duration' => $row->slot?->duration,
                    'status' => $row->status,
                    'is_employed' => in_array($row->status, ['accepted', 'completed'], true),
                    'reviewed_at' => optional($row->reviewed_at)?->toDateString(),
                    'logs_count' => $logs,
                    'attended_days' => $attended,
                    'attendance_rate' => $logs > 0 ? round(($attended / $logs) * 100, 1) : 0,
                    'completion_role' => $row->completion_role,
                    'completion_key_skills' => $row->completion_key_skills ?? [],
                    'completion_start_date' => optional($row->completion_start_date)?->toDateString(),
                    'completion_end_date' => optional($row->completion_end_date)?->toDateString(),
                    'certificate_number' => $row->certificate?->certificate_number,
                ];
            })
            ->values();
    }

    private function studentDirectory()
    {
        return User::where('role', 'student')
            ->withCount('enrollments')
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'email', 'gender', 'state', 'location', 'lga', 'age', 'is_active', 'created_at'])
            ->map(function (User $student) {
                $locationKey = $this->normalizeLocationKey($student->state, $student->location);

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                    'gender' => $student->gender ? ucfirst(strtolower(trim($student->gender))) : null,
                    'state' => $student->state ? trim($student->state) : ($student->location ? trim($student->location) : null),
                    'location_key' => $locationKey,
                    'lga' => $student->lga ? trim($student->lga) : null,
                    'age' => $student->age,
                    'is_active' => (bool) $student->is_active,
                    'enrollments_count' => (int) $student->enrollments_count,
                    'joined_at' => optional($student->created_at)?->toDateString(),
                ];
            })
            ->values();
    }

    private function genderFilters()
    {
        return User::where('role', 'student')
            ->selectRaw("LOWER(COALESCE(NULLIF(TRIM(gender), ''), 'unspecified')) as gender_key, COUNT(*) as value")
            ->groupByRaw("LOWER(COALESCE(NULLIF(TRIM(gender), ''), 'unspecified'))")
            ->orderByDesc('value')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->gender_key === 'unspecified' ? 'Unspecified' : ucfirst($row->gender_key),
                'value' => (int) $row->value,
                'key' => (string) $row->gender_key,
            ])
            ->values();
    }

    private function locationFilters()
    {
        // Case-insensitive grouping; fall back to free-text `location` when `state` is empty.
        // Without LOWER(), "Lagos" / "lagos" split counts and the UI filter key never matches every row.
        return User::where('role', 'student')
            ->selectRaw("LOWER(COALESCE(NULLIF(TRIM(state), ''), NULLIF(TRIM(location), ''), 'unspecified')) as location_key, COUNT(*) as value")
            ->groupByRaw("LOWER(COALESCE(NULLIF(TRIM(state), ''), NULLIF(TRIM(location), ''), 'unspecified'))")
            ->orderByDesc('value')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->location_key === 'unspecified'
                    ? 'Unspecified'
                    : ucwords(str_replace(['_', '-'], ' ', (string) $row->location_key)),
                'value' => (int) $row->value,
                'key' => (string) $row->location_key,
            ])
            ->values();
    }

    private function normalizeLocationKey(?string $state, ?string $location = null): string
    {
        $raw = trim((string) ($state ?: $location ?: ''));

        return $raw === '' ? 'unspecified' : strtolower($raw);
    }

    private function ageBreakdown()
    {
        return User::where('role', 'student')
            ->whereNotNull('age')
            ->selectRaw("CASE
                WHEN age < 25 THEN '18-24'
                WHEN age < 35 THEN '25-34'
                WHEN age < 45 THEN '35-44'
                WHEN age < 55 THEN '45-54'
                ELSE '55+' END as bracket, COUNT(*) as value")
            ->groupByRaw("CASE
                WHEN age < 25 THEN '18-24'
                WHEN age < 35 THEN '25-34'
                WHEN age < 45 THEN '35-44'
                WHEN age < 55 THEN '45-54'
                ELSE '55+' END")
            ->orderByDesc('value')
            ->get()
            ->map(fn ($row) => ['label' => $row->bracket, 'value' => (int) $row->value])
            ->values();
    }

    /**
     * Monthly counts (or sums, if $sumColumn is given) for the last $months months,
     * zero-filled so the chart always has a full, continuous x-axis. Grouping is
     * driver-aware the same way App\Filament\Widgets\AdminChartStatsWidget already
     * branches on the sqlite vs mysql/pg date functions, just at month granularity.
     */
    private function monthlyTrend(Builder $query, string $dateColumn = 'created_at', ?string $sumColumn = null, int $months = self::TREND_MONTHS): array
    {
        $periodExpr = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$dateColumn})"
            : "DATE_FORMAT({$dateColumn}, '%Y-%m')";

        $valueExpr = $sumColumn ? "SUM({$sumColumn})" : 'COUNT(*)';

        $rows = $query
            ->where($dateColumn, '>=', now()->subMonths($months - 1)->startOfMonth())
            ->selectRaw("{$periodExpr} as period, {$valueExpr} as value")
            ->groupBy('period')
            ->pluck('value', 'period');

        $points = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');
            $points[] = [
                'label' => $date->format('M Y'),
                'value' => (float) ($rows[$key] ?? 0),
            ];
        }

        return $points;
    }

    private function resolvePartner(Request $request)
    {
        $user = $request->user();

        // Preferred: dedicated funder role.
        if ($user->role === 'partner') {
            return $user;
        }

        // Transitional: pre-split accounts used organisation + dashboard_permissions.
        if ($user->role === 'organisation') {
            $organisation = $user->organisation;
            $granted = $organisation?->dashboard_permissions
                ?? $user->dashboard_permissions
                ?? [];

            if ($organisation && ! empty($granted)) {
                // Expose permissions on the user object so the rest of the controller stays role-agnostic.
                $user->dashboard_permissions = $granted;
                if (! $user->name && $organisation->name) {
                    $user->name = $organisation->name;
                }

                return $user;
            }

            return response()->json([
                'success' => false,
                'message' => 'This organisation account has no partner dashboard access. Ask an admin to create a Partner (funder) account, or grant dashboard sections.',
            ], 403);
        }

        return response()->json([
            'success' => false,
            'message' => 'The partner dashboard is only available for partner (funder) accounts.',
        ], 403);
    }

    /**
     * Filament repeaters store rows as [{item: "..."}], while some fields are plain strings.
     * Always return a flat list of non-empty strings for the partner UI.
     *
     * @param  mixed  $value
     * @return list<string>
     */
    private function normalizeStringList($value): array
    {
        if (is_string($value)) {
            return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $this->plainText($value)) ?: [])));
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(function ($row) {
                if (is_string($row) || is_numeric($row)) {
                    return $this->plainText((string) $row);
                }

                if (is_array($row)) {
                    foreach (['item', 'label', 'text', 'value', 'title', 'option', 'question', 'explanation'] as $key) {
                        if (isset($row[$key]) && (is_string($row[$key]) || is_numeric($row[$key]))) {
                            return $this->plainText((string) $row[$key]);
                        }
                    }
                }

                return null;
            })
            ->filter()
            ->values()
            ->all();
    }

    private function plainText(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $text = preg_replace('/<\s*br\s*\/?>/i', "\n", $value) ?? $value;
        $text = preg_replace('/<\s*\/\s*p\s*>/i', "\n\n", $text) ?? $text;
        $text = preg_replace('/<\s*\/\s*li\s*>/i', "\n", $text) ?? $text;
        $text = preg_replace('/<\s*li[^>]*>/i', '• ', $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        $trimmed = trim($text);

        return $trimmed === '' ? null : $trimmed;
    }
}
