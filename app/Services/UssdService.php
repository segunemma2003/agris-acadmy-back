<?php

namespace App\Services;

use App\Models\Category;
use App\Models\ChatbotIntakeAnswer;
use App\Models\ChatbotSession;
use App\Models\Course;
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
    private const CACHE_TTL = 600; // 10 min USSD session cache

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

            return $this->con($this->t($lang,
                "Main Menu:\n1. Browse Courses\n2. Talk to Facilitator\n3. Register via SMS link",
                "Babban Menu:\n1. Duba Darussan\n2. Magana da Taimakawa\n3. Yi Rijista ta SMS"
            ));
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

        return $this->end($this->t($lang,
            "Invalid option. Please dial again.",
            "Zabin ba daidai ba. Da fatan za a sake bugawa."
        ));
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
            return $this->con($this->t($lang,
                "Main Menu:\n1. Browse Courses\n2. Talk to Facilitator\n3. Register via SMS link",
                "Babban Menu:\n1. Duba Darussan\n2. Magana da Taimakawa\n3. Yi Rijista ta SMS"
            ));
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
            $desc = $this->truncate(
                $course['title'] . ": " . ($course['short_description'] ?? '') .
                "\n1. " . $this->t($lang, "I'm interested", "Ina sha'awa") .
                "\n2. " . $this->t($lang, "Back", "Koma"),
                self::MAX_CHARS
            );

            // Cache selected course for this session
            Cache::put("ussd_course_{$sessionId}", $course, self::CACHE_TTL);

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
            Cache::put("ussd_name_{$sessionId}", $name, self::CACHE_TTL);

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
        Cache::put("ussd_lang_{$sessionId}", $lang, self::CACHE_TTL);
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
        $limit = $max - 4;

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        // Truncate at word boundary
        $truncated = mb_substr($text, 0, $limit);
        $lastSpace = mb_strrpos($truncated, ' ');

        return $lastSpace ? mb_substr($truncated, 0, $lastSpace) . '...' : $truncated;
    }
}
