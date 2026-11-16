<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseRecommendation;
use App\Models\Enrollment;
use App\Models\Module;
use App\Models\ModuleTest;
use App\Models\TestAttempt;
use App\Models\User;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/** COCO ranks courses on strengths (quiz scores, completions, "next course" links), not just interests. */
class CocoStrengthsTest extends TestCase
{
    use RefreshDatabase;

    private Category $poultry;
    private Category $fish;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Notification::fake();
        Queue::fake();
        $this->poultry = Category::create(['name' => 'Poultry', 'slug' => 'poultry']);
        $this->fish = Category::create(['name' => 'Fish', 'slug' => 'fish']);
    }

    private function course(Category $cat, string $title, string $level = 'beginner', int $enrolments = 0): Course
    {
        return Course::create([
            'category_id' => $cat->id, 'title' => $title, 'slug' => str($title)->slug(), 'description' => 'x',
            'is_published' => true, 'level' => $level, 'enrollment_count' => $enrolments,
        ]);
    }

    private function learner(): User
    {
        return User::factory()->create(['role' => 'student', 'is_active' => true]);
    }

    private function quiz(User $user, Course $course, float $pct): void
    {
        $module = Module::create(['course_id' => $course->id, 'title' => 'M', 'sort_order' => 1, 'is_active' => true]);
        $test = ModuleTest::create(['module_id' => $module->id, 'course_id' => $course->id, 'title' => 'Q', 'passing_score' => 70, 'is_active' => true]);
        TestAttempt::create([
            'user_id' => $user->id, 'module_test_id' => $test->id, 'score' => $pct, 'total_questions' => 10,
            'percentage' => $pct, 'is_passed' => $pct >= 70, 'answers' => [], 'started_at' => now(), 'completed_at' => now(),
        ]);
    }

    public function test_completed_course_steps_up_a_level_and_follows_next_course_link(): void
    {
        $done = $this->course($this->poultry, 'Broilers 101');
        $intermediate = $this->course($this->poultry, 'Commercial Layers', 'intermediate');
        $popularFish = $this->course($this->fish, 'Catfish Basics', 'beginner', 500);
        $linked = $this->course($this->fish, 'Fish Feed Formulation', 'intermediate');
        CourseRecommendation::create(['course_id' => $done->id, 'recommended_course_id' => $linked->id, 'sort_order' => 1]);

        $user = $this->learner();
        Enrollment::create(['user_id' => $user->id, 'course_id' => $done->id, 'status' => 'completed', 'progress_percentage' => 100, 'enrolled_at' => now()]);

        $picks = collect(app(RecommendationService::class)->forUser($user, 3));
        $titles = $picks->pluck('course.title')->all();

        $this->assertNotContains('Broilers 101', $titles, 'already taken');
        $this->assertSame('Fish Feed Formulation', $titles[0], 'admin next-course link after a completed course wins');
        $this->assertStringContainsString('next step after Broilers 101', $picks[0]['course']['rationale']);
        $this->assertSame('Commercial Layers', $titles[1], 'steps up a level in a category they finished');
        $this->assertStringContainsString('strong results in poultry', $picks[1]['course']['rationale']);
    }

    public function test_low_quiz_scores_point_to_a_beginner_course_in_that_subject(): void
    {
        $current = $this->course($this->fish, 'Pond Management', 'intermediate');
        $this->course($this->fish, 'Advanced Hatchery', 'advanced');
        $this->course($this->fish, 'Fish Farming Basics', 'beginner');
        $this->course($this->poultry, 'Poultry Starter', 'beginner', 900);

        $user = $this->learner();
        Enrollment::create(['user_id' => $user->id, 'course_id' => $current->id, 'status' => 'active', 'progress_percentage' => 30, 'enrolled_at' => now()]);
        $this->quiz($user, $current, 35);

        $picks = app(RecommendationService::class)->forUser($user, 3);
        $this->assertSame('Fish Farming Basics', $picks[0]['course']['title']);
        $this->assertStringContainsString('basics of fish', $picks[0]['course']['rationale']);
    }

    public function test_profile_value_chain_is_used_when_learner_never_chatted_with_coco(): void
    {
        $this->course($this->poultry, 'Poultry Starter');
        $this->course($this->fish, 'Catfish Pond Management', 'beginner', 900);
        $user = User::factory()->create(['role' => 'student', 'is_active' => true, 'value_chain_track' => 'poultry']);

        $picks = app(RecommendationService::class)->forUser($user, 2);
        $this->assertSame('Poultry Starter', $picks[0]['course']['title']);
    }

    public function test_recommended_courses_endpoint_returns_coco_reasons(): void
    {
        $done = $this->course($this->poultry, 'Broilers 101');
        $this->course($this->poultry, 'Commercial Layers', 'intermediate');
        $user = $this->learner();
        Enrollment::create(['user_id' => $user->id, 'course_id' => $done->id, 'status' => 'completed', 'progress_percentage' => 100, 'enrolled_at' => now()]);

        $data = $this->actingAs($user, 'sanctum')->getJson('/api/recommended-courses')->assertOk()->json('data');
        $this->assertSame('Commercial Layers', $data[0]['title']);
        $this->assertNotEmpty($data[0]['coco_reason']);
    }
}
