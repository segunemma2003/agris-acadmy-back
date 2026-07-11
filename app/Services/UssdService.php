<?php

namespace App\Services;

use App\Models\Category;
use App\Models\ChatbotIntakeAnswer;
use App\Models\ChatbotSession;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\StudentProgress;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\AdminNotificationMail;

class UssdService
{
    private const MAX_CHARS = 182;

    // All per-session cache (language, in-progress lead capture, selected course)
    // shares this TTL so it expires together with the session itself: 90s of
    // inactivity ends the USSD session gracefully (see the timeout guard in handle()).
    private const SESSION_TTL = 100;

    public function validateHmac(string $rawBody, string $providedHash, string $hashKey): bool
    {
        $expected = hash_hmac('sha256', $rawBody, $hashKey);

        return hash_equals($expected, $providedHash);
    }

    public function handle(array $params): string
    {
        $sessionId   = $params['sessionId']   ?? '';
        $phoneNumber = $params['phoneNumber'] ?? '';
        $text        = $params['text']        ?? '';

        $steps = $text === '' ? [] : explode('*', $text);
        $depth = count($steps);

        $lang = $this->getLanguage($sessionId);

        // Graceful 90s inactivity timeout: every real step refreshes an activity
        // marker for this sessionId. If a step arrives (depth > 0, i.e. not the
        // very first request of a session) and the marker has expired, the gap
        // since the last request exceeded the timeout window, so end the session
        // with a clear message instead of silently restarting the menu tree.
        if ($depth > 0 && !Cache::has("ussd_active_{$sessionId}")) {
            return $this->end($this->t($lang,
                "Session ended due to inactivity. Please dial again.",
                "An kawo karshen zaman saboda rashin aiki. Da fatan za a sake bugawa."
            ));
        }
        Cache::put("ussd_active_{$sessionId}", true, self::SESSION_TTL);

        // Step 0: Language selection
        if ($depth === 0) {
            return $this->con($this->t($lang,
                "Welcome to Agrisiti Academy.\n1. English\n2. Hausa",
                "Barka da zuwa Agrisiti Academy.\n1. English\n2. Hausa"
            ));
        }

        // Step 1: Language chosen
        if ($depth === 1) {
            $lang = $steps[0] === '2' ? 'ha' : 'en';
            $this->setLanguage($sessionId, $lang);

            return $this->con($this->mainMenuText($lang));
        }

        // Step 2: Main menu choice
        $mainChoice = $steps[1] ?? '';

        if ($mainChoice === '1') {
            return $this->handleBrowseCourses($steps, $lang, $sessionId, $phoneNumber);
        }

        if ($mainChoice === '2') {
            return $this->handleFacilitator($steps, $lang, $phoneNumber, $sessionId);
        }

        if ($mainChoice === '3') {
            return $this->handleRegisterLink($steps, $lang, $phoneNumber);
        }

        if ($mainChoice === '4') {
            return $this->handleMyCourses($steps, $lang, $sessionId, $phoneNumber);
        }

        if ($mainChoice === '5') {
            return $this->handleApprenticeshipLog($steps, $lang, $sessionId, $phoneNumber);
        }

        return $this->end($this->t($lang,
            "Invalid option. Please dial again.",
            "Zabin ba daidai ba. Da fatan za a sake bugawa."
        ));
    }

    private function mainMenuText(string $lang): string
    {
        return $this->t($lang,
            "Main Menu:\n1. Browse Courses\n2. Talk to Facilitator\n3. Register via SMS link\n4. My Courses\n5. Log Apprenticeship",
            "Babban Menu:\n1. Duba Darussan\n2. Magana da Taimakawa\n3. Yi Rijista ta SMS\n4. Darussana\n5. Rubuta Horon Aiki"
        );
    }

    /**
     * "Log today? 1=Yes, 2=No" -> optional short note, for a learner with an
     * active (accepted) apprenticeship placement. Writes to the same
     * apprenticeship_logs table the web daily-log form uses.
     */
    private function handleApprenticeshipLog(array $steps, string $lang, string $sessionId, string $phone): string
    {
        $depth = count($steps);
        $user = $this->findUserByPhone($phone);

        if (!$user) {
            return $this->end($this->t($lang,
                "No account found for this number. Dial 3 from the main menu to get a registration link by SMS.",
                "Ba a sami asusu don wannan lambar ba. Ka buga 3 daga babban menu don karɓar hanyar rijista ta SMS."
            ));
        }

        $apprenticeship = \App\Models\Apprenticeship::where('user_id', $user->id)
            ->where('status', 'accepted')
            ->first();

        if (!$apprenticeship) {
            return $this->end($this->t($lang,
                "You have no active apprenticeship placement to log.",
                "Ba ka da horon aiki mai aiki don rubutawa a yanzu."
            ));
        }

        if ($depth === 2) {
            return $this->con($this->t($lang,
                "Log today? 1. Yes 2. No",
                "Rubuta yau? 1. Ee 2. A'a"
            ));
        }

        if ($depth === 3) {
            $choice = $steps[2] ?? '';

            if (!in_array($choice, ['1', '2'], true)) {
                return $this->end($this->t($lang, "Invalid option.", "Zabin ba daidai ba."));
            }

            Cache::put("ussd_apprenticeship_attended_{$sessionId}", $choice === '1', self::SESSION_TTL);

            return $this->con($this->t($lang,
                "Add a short note (optional). Reply with a space to skip:",
                "Ka rubuta gajeriyar bayani (ba dole ba ne). Ka rubuta sarari don tsallakewa:"
            ));
        }

        if ($depth === 4) {
            $attended = Cache::get("ussd_apprenticeship_attended_{$sessionId}");

            if ($attended === null) {
                return $this->end($this->t($lang,
                    "Session expired. Please dial again.",
                    "Zaman ya kare. Da fatan za a sake bugawa."
                ));
            }

            $note = trim(mb_substr($steps[3] ?? '', 0, 160));

            \App\Models\ApprenticeshipLog::updateOrCreate(
                [
                    'apprenticeship_id' => $apprenticeship->id,
                    'log_date' => now()->toDateString(),
                ],
                [
                    'attended' => $attended,
                    'activity_description' => $note !== '' ? $note : null,
                    'source' => 'ussd',
                ]
            );

            return $this->end($this->t($lang,
                "Thank you! Today's apprenticeship log has been recorded.",
                "Na gode! An rubuta horon aiki na yau."
            ));
        }

        return $this->end($this->t($lang, "Invalid option.", "Zabin ba daidai ba."));
    }

    private function handleBrowseCourses(array $steps, string $lang, string $sessionId, string $phone): string
    {
        $depth = count($steps);

        if ($depth === 2) {
            // Show categories
            $categories = $this->getCategories();
            $menu = $this->t($lang, "Select a course category:", "Zaɓi rukunin darasi:");
            foreach ($categories as $i => $cat) {
                $menu .= "\n" . ($i + 1) . ". " . $cat['name'];
            }
            $menu .= "\n" . (count($categories) + 1) . ". " . $this->t($lang, "Back", "Koma");

            return $this->con($this->truncate($menu));
        }

        $categoryIndex = (int) ($steps[2] ?? 0) - 1;
        $categories = $this->getCategories();

        if ($categoryIndex === count($categories)) {
            // Back to main menu
            return $this->con($this->mainMenuText($lang));
        }

        if ($depth === 3) {
            // Show courses in selected category
            if (!isset($categories[$categoryIndex])) {
                return $this->end($this->t($lang, "Invalid option.", "Zabin ba daidai ba."));
            }

            $categoryId = $categories[$categoryIndex]['id'];
            $courses = $this->getCoursesByCategory($categoryId);

            if (empty($courses)) {
                return $this->end($this->t($lang,
                    "No courses available in this category yet.",
                    "Babu darussan da ake da su a wannan rukunin tukuna."
                ));
            }

            $menu = $this->t($lang, "Courses:", "Darussan:");
            foreach ($courses as $i => $course) {
                $menu .= "\n" . ($i + 1) . ". " . $course['title'];
            }
            $menu .= "\n" . (count($courses) + 1) . ". " . $this->t($lang, "Back", "Koma");

            return $this->con($this->truncate($menu));
        }

        if ($depth === 4) {
            // Show course detail
            $categoryId = $categories[$categoryIndex]['id'];
            $courses = $this->getCoursesByCategory($categoryId);
            $courseIndex = (int) ($steps[3] ?? 0) - 1;

            if ($courseIndex === count($courses)) {
                // Back to category list
                $categories = $this->getCategories();
                $menu = $this->t($lang, "Select a course category:", "Zaɓi rukunin darasi:");
                foreach ($categories as $i => $cat) {
                    $menu .= "\n" . ($i + 1) . ". " . $cat['name'];
                }
                return $this->con($this->truncate($menu));
            }

            if (!isset($courses[$courseIndex])) {
                return $this->end($this->t($lang, "Invalid option.", "Zabin ba daidai ba."));
            }

            $course = $courses[$courseIndex];

            // Truncate only the description, never the menu: a long
            // short_description used to eat the "1. I'm interested / 2. Back"
            // options entirely, leaving the learner with cut-off text and no
            // visible way to proceed or go back.
            $header = $course['title'] . ": ";
            $menuSuffix = "\n1. " . $this->t($lang, "I'm interested", "Ina sha'awa") .
                "\n2. " . $this->t($lang, "Back", "Koma");
            $descriptionBudget = (self::MAX_CHARS - 4) - mb_strlen($header) - mb_strlen($menuSuffix);
            $description = $this->truncateToLength($course['short_description'] ?? '', $descriptionBudget);

            $desc = $header . $description . $menuSuffix;

            // Cache selected course for this session
            Cache::put("ussd_course_{$sessionId}", $course, self::SESSION_TTL);

            return $this->con($desc);
        }

        if ($depth === 5) {
            $interest = $steps[4] ?? '';

            if ($interest === '2') {
                // Back to course list
                return $this->handleBrowseCourses(array_slice($steps, 0, 3), $lang, $sessionId, $phone);
            }

            if ($interest === '1') {
                // Collect name
                return $this->con($this->t($lang,
                    "Great! Enter your full name:",
                    "Sannu! Shigar da sunan ka cikakke:"
                ));
            }
        }

        if ($depth === 6) {
            // Name entered, ask for phone
            $name = $steps[5] ?? '';
            Cache::put("ussd_name_{$sessionId}", $name, self::SESSION_TTL);

            return $this->con($this->t($lang,
                "Enter your phone number:",
                "Shigar da lambar wayarka:"
            ));
        }

        if ($depth === 7) {
            // Phone entered — save lead
            $userPhone = $steps[6] ?? '';
            $name = Cache::get("ussd_name_{$sessionId}", '');
            $course = Cache::get("ussd_course_{$sessionId}");

            if (!$this->validateNigerianPhone($userPhone)) {
                return $this->con($this->t($lang,
                    "Invalid phone number. Please enter a valid Nigerian number (e.g. 08012345678):",
                    "Lambar waya ba daidai ba. Ka shigar da lambar waya ta Najeriya (misali 08012345678):"
                ));
            }

            $this->saveLead([
                'source'              => 'ussd',
                'name'                => $name,
                'phone'               => $userPhone,
                'interested_course_id'=> $course['id'] ?? null,
                'course_title'        => $course['title'] ?? '',
            ]);

            $this->sendConfirmationSms($userPhone, $course['title'] ?? 'a course', $lang);

            Cache::forget("ussd_name_{$sessionId}");
            Cache::forget("ussd_course_{$sessionId}");

            return $this->end($this->t($lang,
                "Your interest has been recorded. Our team will call you soon. Thank you!",
                "An yi rijistar sha'awarka. Tawagar mu za ta tuntuɓe ka nan ba da jimawa ba. Na gode!"
            ));
        }

        return $this->end($this->t($lang, "Invalid option.", "Zabin ba daidai ba."));
    }

    private function handleFacilitator(array $steps, string $lang, string $phone, string $sessionId): string
    {
        $depth = count($steps);

        if ($depth === 2) {
            return $this->con($this->t($lang,
                "Enter your phone number to receive facilitator contact:",
                "Shigar da lambar wayarka don karɓar lambar mai taimakawa:"
            ));
        }

        if ($depth === 3) {
            $userPhone = $steps[2] ?? '';

            if (!$this->validateNigerianPhone($userPhone)) {
                return $this->con($this->t($lang,
                    "Invalid number. Enter a valid Nigerian phone (e.g. 08012345678):",
                    "Lambar ba daidai ba. Shigar da lambar waya ta Najeriya:"
                ));
            }

            $this->saveLead(['source' => 'ussd', 'phone' => $userPhone, 'learning_goal' => 'facilitator']);
            $this->sendSms($userPhone, $this->t($lang,
                "Hello! A facilitator from Agrisiti Academy will contact you shortly.",
                "Sannu! Mai taimakawa daga Agrisiti Academy zai tuntuɓe ka nan ba da jimawa ba."
            ));

            return $this->end($this->t($lang,
                "A facilitator will contact you shortly. Thank you!",
                "Mai taimakawa zai tuntuɓe ka nan ba da jimawa ba. Na gode!"
            ));
        }

        return $this->end($this->t($lang, "Invalid option.", "Zabin ba daidai ba."));
    }

    private function handleRegisterLink(array $steps, string $lang, string $phone): string
    {
        $depth = count($steps);

        if ($depth === 2) {
            return $this->con($this->t($lang,
                "Enter your phone number to receive the registration link:",
                "Shigar da lambar wayarka don karɓar hanyar rijista:"
            ));
        }

        if ($depth === 3) {
            $userPhone = $steps[2] ?? '';

            if (!$this->validateNigerianPhone($userPhone)) {
                return $this->con($this->t($lang,
                    "Invalid number. Enter a valid Nigerian phone (e.g. 08012345678):",
                    "Lambar ba daidai ba. Shigar da lambar waya ta Najeriya:"
                ));
            }

            $registerUrl = config('app.url') . '/register';
            $this->sendSms($userPhone, $this->t($lang,
                "Register at Agrisiti Academy here: {$registerUrl}",
                "Yi rijista a Agrisiti Academy nan: {$registerUrl}"
            ));
            $this->saveLead(['source' => 'ussd', 'phone' => $userPhone]);

            return $this->end($this->t($lang,
                "Registration link sent to your number. Thank you!",
                "An aika hanyar rijista zuwa lambar wayarka. Na gode!"
            ));
        }

        return $this->end($this->t($lang, "Invalid option.", "Zabin ba daidai ba."));
    }

    /**
     * "My Courses": progress checking and lesson completion for enrolled learners.
     * The learner is identified by the calling number Africa's Talking already
     * supplies (no PIN/login step), matched against User::phone.
     */
    private function handleMyCourses(array $steps, string $lang, string $sessionId, string $phone): string
    {
        $depth = count($steps);
        $user = $this->findUserByPhone($phone);

        if (!$user) {
            return $this->end($this->t($lang,
                "No account found for this number. Dial 3 from the main menu to get a registration link by SMS.",
                "Ba a sami asusu don wannan lambar ba. Ka buga 3 daga babban menu don karɓar hanyar rijista ta SMS."
            ));
        }

        if ($depth === 2) {
            $enrollments = Enrollment::where('user_id', $user->id)
                ->whereIn('status', ['active', 'completed'])
                ->with('course:id,title')
                ->get()
                ->filter(fn ($enrollment) => $enrollment->course !== null)
                ->values();

            if ($enrollments->isEmpty()) {
                return $this->end($this->t($lang,
                    "You are not enrolled in any courses yet.",
                    "Ba ka yi rijistar wani darasi ba tukuna."
                ));
            }

            Cache::put(
                "ussd_my_courses_{$sessionId}",
                $enrollments->pluck('course_id')->all(),
                self::SESSION_TTL
            );

            $menu = $this->t($lang, "My Courses:", "Darussana:");
            foreach ($enrollments as $i => $enrollment) {
                $menu .= "\n" . ($i + 1) . ". " . $enrollment->course->title;
            }
            $menu .= "\n" . ($enrollments->count() + 1) . ". " . $this->t($lang, "Back", "Koma");

            return $this->con($this->truncate($menu));
        }

        $courseIds = Cache::get("ussd_my_courses_{$sessionId}", []);
        $courseIndex = (int) ($steps[2] ?? 0) - 1;

        if ($courseIndex === count($courseIds)) {
            // Back to main menu
            return $this->con($this->mainMenuText($lang));
        }

        if (!isset($courseIds[$courseIndex])) {
            return $this->end($this->t($lang,
                "Session expired. Please dial again.",
                "Zaman ya kare. Da fatan za a sake bugawa."
            ));
        }

        $course = Course::find($courseIds[$courseIndex]);

        if (!$course) {
            return $this->end($this->t($lang, "Course not found.", "Ba a sami darasi ba."));
        }

        if ($depth === 3) {
            Cache::put("ussd_my_course_{$sessionId}", $course->id, self::SESSION_TTL);

            $menu = $this->truncate($this->t($lang,
                $course->title . "\n1. Check my progress\n2. Continue course\n3. Contact facilitator\n4. Back",
                $course->title . "\n1. Duba ci gabana\n2. Ci gaba da darasi\n3. Magana da mai taimakawa\n4. Koma"
            ));

            return $this->con($menu);
        }

        // depth 4: action chosen for the selected course
        $action = $steps[3] ?? '';

        if ($action === '4') {
            // Back to course list
            return $this->handleMyCourses(array_slice($steps, 0, 2), $lang, $sessionId, $phone);
        }

        if ($action === '1') {
            return $this->end($this->buildProgressMessage($user, $course, $lang));
        }

        if ($action === '2') {
            return $this->end($this->completeTodaysLesson($user, $course, $lang));
        }

        if ($action === '3') {
            $this->saveLead([
                'source'       => 'ussd',
                'phone'        => $phone,
                'learning_goal'=> 'facilitator',
                'course_title' => $course->title,
            ]);
            $this->sendSms($phone, $this->t($lang,
                "Hello! A facilitator from Agrisiti Academy will contact you shortly about '{$course->title}'.",
                "Sannu! Mai taimakawa daga Agrisiti Academy zai tuntuɓe ka nan ba da jimawa ba game da '{$course->title}'."
            ));

            return $this->end($this->t($lang,
                "A facilitator will contact you shortly. Thank you!",
                "Mai taimakawa zai tuntuɓe ka nan ba da jimawa ba. Na gode!"
            ));
        }

        return $this->end($this->t($lang, "Invalid option.", "Zabin ba daidai ba."));
    }

    /**
     * Match the number Africa's Talking supplies (e.g. +2348012345678) against
     * User::phone, which is stored free-form (08..., 234..., +234..., with or
     * without spaces) since registration never normalizes it.
     */
    private function findUserByPhone(string $phoneNumber): ?User
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber);
        $national = preg_match('/^234(\d{10})$/', $digits, $m) || preg_match('/^0?(\d{10})$/', $digits, $m)
            ? $m[1]
            : $digits;

        return User::whereIn('phone', [$national, "0{$national}", "234{$national}", "+234{$national}"])
            ->orWhere('phone', 'like', "%{$national}")
            ->first();
    }

    /**
     * "Module X of Y complete": X is the count of modules where every topic is
     * marked complete for this learner; Y is the course's active module count.
     */
    private function buildProgressMessage(User $user, Course $course, string $lang): string
    {
        $modules = $course->modules()->where('is_active', true)->with('topics')->get();
        $totalModules = $modules->count();

        if ($totalModules === 0) {
            return $this->truncate($this->t($lang,
                "{$course->title} has no modules yet.",
                "{$course->title} ba shi da darussa tukuna."
            ));
        }

        $completedTopicIds = StudentProgress::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('is_completed', true)
            ->pluck('topic_id')
            ->all();

        $completedModules = $modules->filter(function ($module) use ($completedTopicIds) {
            return $module->topics->isNotEmpty()
                && $module->topics->every(fn ($topic) => in_array($topic->id, $completedTopicIds, true));
        })->count();

        return $this->truncate($this->t($lang,
            "{$course->title}\nModule {$completedModules} of {$totalModules} complete.",
            "{$course->title}\nAn kammala Darasi {$completedModules} daga cikin {$totalModules}."
        ));
    }

    /**
     * Marks the learner's next incomplete topic (in module/topic sort order) as
     * done, writing to the same student_progress table the web and mobile apps
     * use, so the change is reflected immediately everywhere.
     */
    private function completeTodaysLesson(User $user, Course $course, string $lang): string
    {
        $topics = $course->modules()
            ->where('is_active', true)
            ->with('topics')
            ->get()
            ->flatMap(fn ($module) => $module->topics);

        $completedTopicIds = StudentProgress::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('is_completed', true)
            ->pluck('topic_id')
            ->all();

        $nextTopic = $topics->first(fn ($topic) => !in_array($topic->id, $completedTopicIds, true));

        if (!$nextTopic) {
            return $this->truncate($this->t($lang,
                "You've already completed all lessons in {$course->title}. Great job!",
                "Ka riga ka kammala dukkan darussa a {$course->title}. Aikin gwani ne!"
            ));
        }

        StudentProgress::updateOrCreate(
            ['user_id' => $user->id, 'topic_id' => $nextTopic->id],
            [
                'course_id' => $course->id,
                'is_completed' => true,
                'completion_percentage' => 100,
                'completed_at' => now(),
                'last_accessed_at' => now(),
            ]
        );

        $this->updateEnrollmentProgress($user, $course);

        $completedCount = count($completedTopicIds) + 1;
        $totalCount = $topics->count();

        return $this->truncate($this->t($lang,
            "'{$nextTopic->title}' marked as complete! ({$completedCount} of {$totalCount} lessons done in {$course->title}.)",
            "An kammala '{$nextTopic->title}'! ({$completedCount} daga cikin {$totalCount} darussa a {$course->title}.)"
        ));
    }

    /**
     * Mirrors ProgressController::updateEnrollmentProgress() so USSD-driven
     * completions roll up into the enrollment the same way web/mobile do.
     */
    private function updateEnrollmentProgress(User $user, Course $course): void
    {
        $user->recordActivity();

        $totalTopics = $course->modules()->withCount('topics')->get()->sum('topics_count');
        $completedTopics = StudentProgress::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('is_completed', true)
            ->count();

        $progressPercentage = $totalTopics > 0 ? ($completedTopics / $totalTopics) * 100 : 0;

        $enrollment = Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->first();

        if ($enrollment) {
            $enrollment->update(['progress_percentage' => round($progressPercentage, 2)]);

            if ($progressPercentage >= 100 && $enrollment->status !== 'completed') {
                $enrollment->update(['status' => 'completed', 'completed_at' => now()]);
            }
        }
    }

    public function validateNigerianPhone(string $phone): bool
    {
        // Matches 08XXXXXXXXX, 07XXXXXXXXX, 09XXXXXXXXX, +234XXXXXXXXXX, 234XXXXXXXXXX
        return (bool) preg_match('/^(?:\+?234|0)[789]\d{9}$/', preg_replace('/\s+/', '', $phone));
    }

    public function saveLead(array $data): void
    {
        try {
            // Create anonymous session for USSD lead
            $session = ChatbotSession::create([
                'session_token' => (string) Str::uuid(),
                'metadata'      => ['source' => 'ussd', 'phone' => $data['phone'] ?? null],
                'last_seen_at'  => now(),
            ]);

            ChatbotIntakeAnswer::create([
                'chatbot_session_id'  => $session->id,
                'source'              => $data['source'] ?? 'ussd',
                'name'                => $data['name'] ?? null,
                'phone'               => $data['phone'] ?? null,
                'learning_goal'       => $data['learning_goal'] ?? null,
                'interested_course_id'=> $data['interested_course_id'] ?? null,
            ]);

            // Notify all admins
            $admins = User::where('role', 'admin')->where('is_active', true)->get();
            $courseName = $data['course_title'] ?? ($data['learning_goal'] ?? 'unspecified');
            $message = "New USSD lead from {$data['phone']}. Interested in: {$courseName}.";

            NotificationService::createForRole('admin', 'new_lead', 'New USSD Lead', $message, 'chatbot_lead', $session->id);

            foreach ($admins as $admin) {
                Mail::to($admin->email)->queue(
                    new AdminNotificationMail($admin, 'New USSD Lead — Agrisiti Academy', $message)
                );
            }
        } catch (\Throwable $e) {
            Log::error('UssdService::saveLead failed', ['error' => $e->getMessage()]);
        }
    }

    private function sendConfirmationSms(string $phone, string $courseName, string $lang): void
    {
        $message = $this->t($lang,
            "Your interest in '{$courseName}' has been recorded. Our team will call you soon. - Agrisiti Academy",
            "An yi rijistar sha'awarka a '{$courseName}'. Tawagar mu za ta tuntuɓe ka. - Agrisiti Academy"
        );
        $this->sendSms($phone, $message);
    }

    public function sendSms(string $phone, string $message): void
    {
        try {
            $username = config('services.africastalking.username');
            $apiKey   = config('services.africastalking.key');

            if (!$apiKey || !$username) {
                Log::warning('Africa\'s Talking SMS not configured');
                return;
            }

            Http::withHeaders([
                'apiKey' => $apiKey,
                'Accept' => 'application/json',
            ])->asForm()->post('https://api.africastalking.com/version1/messaging', [
                'username' => $username,
                'to'       => $phone,
                'message'  => $message,
            ]);
        } catch (\Throwable $e) {
            Log::error('SMS dispatch failed', ['phone' => $phone, 'error' => $e->getMessage()]);
        }
    }

    private function getLanguage(string $sessionId): string
    {
        return Cache::get("ussd_lang_{$sessionId}", 'en');
    }

    private function setLanguage(string $sessionId, string $lang): void
    {
        Cache::put("ussd_lang_{$sessionId}", $lang, self::SESSION_TTL);
    }

    private function getCategories(): array
    {
        return Cache::remember('ussd_categories', 60, function () {
            return Category::orderBy('name')->get()->map(fn ($c) => [
                'id'   => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
            ])->toArray();
        });
    }

    private function getCoursesByCategory(int $categoryId): array
    {
        return Cache::remember("ussd_courses_{$categoryId}", 60, function () use ($categoryId) {
            return Course::where('category_id', $categoryId)
                ->where('is_published', true)
                ->orderByDesc('enrollment_count')
                ->limit(5)
                ->get()
                ->map(fn ($c) => [
                    'id'                => $c->id,
                    'title'             => $c->title,
                    'short_description' => $c->short_description,
                ])->toArray();
        });
    }

    private function t(string $lang, string $en, string $ha): string
    {
        return $lang === 'ha' ? $ha : $en;
    }

    private function con(string $message): string
    {
        return 'CON ' . $this->truncate($message);
    }

    private function end(string $message): string
    {
        return 'END ' . $this->truncate($message);
    }

    public function truncate(string $text, int $max = self::MAX_CHARS): string
    {
        // Account for "CON " or "END " prefix (4 chars)
        return $this->truncateToLength($text, $max - 4);
    }

    /**
     * Truncate at a word boundary to an exact character budget (no CON/END
     * prefix reservation). Used when only part of a message — e.g. a course
     * description — should be shortened, leaving a fixed suffix like a menu
     * untouched, rather than truncating the whole combined string and risking
     * cutting the menu options off entirely.
     */
    private function truncateToLength(string $text, int $limit): string
    {
        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        // Truncate at word boundary
        $truncated = mb_substr($text, 0, max($limit, 0));
        $lastSpace = mb_strrpos($truncated, ' ');

        return $lastSpace ? mb_substr($truncated, 0, $lastSpace) . '...' : $truncated;
    }
}
