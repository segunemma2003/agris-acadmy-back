<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseVrCompletion;
use App\Models\CourseVrContent;
use App\Models\CourseVrSimAttempt;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * VR Studio simulations (fish pond lessons): learners save attempts, passing
 * marks the item complete, tutors see class results, and the course VR list
 * tells the Academy which items are simulations and how the learner did.
 */
class VrSimulationAttemptsTest extends TestCase
{
    use RefreshDatabase;

    private User $tutor;
    private User $learner;
    private Course $course;
    private CourseVrContent $sim;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tutor = User::factory()->create(['role' => 'tutor', 'is_active' => true]);
        $this->learner = User::factory()->create(['role' => 'learner', 'is_active' => true]);
        $category = Category::create(['name' => 'Aquaculture', 'slug' => 'aquaculture']);
        $this->course = Course::create([
            'category_id' => $category->id,
            'tutor_id' => $this->tutor->id,
            'title' => 'Fish Farming',
            'slug' => 'fish-farming',
            'description' => 'Ponds',
            'is_published' => true,
        ]);
        Enrollment::create(['user_id' => $this->learner->id, 'course_id' => $this->course->id, 'status' => 'active', 'enrolled_at' => now()]);
        $this->sim = CourseVrContent::create([
            'course_id' => $this->course->id,
            'title' => 'Night oxygen crash',
            'studio_status' => 'published',
            'studio_slug' => 'sim-night',
            'vr_url' => 'http://localhost:5174/play/sim-night',
            'is_active' => true,
            'scene_json' => ['version' => 2, 'engine' => 'sim-fish-pond', 'lesson' => ['id' => 'night', 'title' => 'Night']],
        ]);
    }

    private function attempt(bool $passed, int $score): array
    {
        return [
            'lesson_id' => 'night',
            'mode' => '3d',
            'outcome' => ['passed' => $passed, 'score' => $score, 'correctAnswers' => 1, 'totalQuestions' => 2, 'reasons' => ['r']],
            'actions' => [['hour' => 3, 'id' => 'water_change', 'amount' => 30]],
            'answers' => [['questionId' => 'gasping', 'optionIndex' => 1, 'correct' => false, 'hour' => 3]],
            'summary' => ['fishAlive' => 70],
        ];
    }

    public function test_learner_attempts_are_saved_and_passing_completes_the_item(): void
    {
        Sanctum::actingAs($this->learner);

        $this->postJson("/api/vr-studio/experiences/{$this->sim->id}/attempts", $this->attempt(false, 30))
            ->assertOk()->assertJsonPath('data.saved', true)->assertJsonPath('data.best_score', 30);
        $this->assertFalse(CourseVrCompletion::where('user_id', $this->learner->id)->exists());

        $this->postJson("/api/vr-studio/experiences/{$this->sim->id}/attempts", $this->attempt(true, 85))
            ->assertOk()->assertJsonPath('data.best_score', 85);
        $this->assertTrue(CourseVrCompletion::where('user_id', $this->learner->id)->exists());
        $this->assertSame(2, CourseVrSimAttempt::count());
    }

    public function test_unenrolled_learner_cannot_save_and_tutor_preview_is_not_stored(): void
    {
        $stranger = User::factory()->create(['role' => 'learner', 'is_active' => true]);
        Sanctum::actingAs($stranger);
        $this->postJson("/api/vr-studio/experiences/{$this->sim->id}/attempts", $this->attempt(true, 90))->assertForbidden();

        Sanctum::actingAs($this->tutor);
        $this->postJson("/api/vr-studio/experiences/{$this->sim->id}/attempts", $this->attempt(true, 90))
            ->assertOk()->assertJsonPath('data.saved', false);
        $this->assertSame(0, CourseVrSimAttempt::count());
    }

    public function test_tutor_sees_class_results_but_learner_does_not(): void
    {
        Sanctum::actingAs($this->learner);
        $this->postJson("/api/vr-studio/experiences/{$this->sim->id}/attempts", $this->attempt(false, 30))->assertOk();
        $this->postJson("/api/vr-studio/experiences/{$this->sim->id}/attempts", $this->attempt(true, 90))->assertOk();
        $this->getJson("/api/vr-studio/experiences/{$this->sim->id}/attempts")->assertForbidden();

        Sanctum::actingAs($this->tutor);
        $this->getJson("/api/vr-studio/experiences/{$this->sim->id}/attempts")
            ->assertOk()
            ->assertJsonPath('data.summary.attempts', 2)
            ->assertJsonPath('data.summary.learners', 1)
            ->assertJsonPath('data.summary.pass_rate', 50)
            ->assertJsonPath('data.summary.most_missed_questions.gasping', 2)
            ->assertJsonCount(2, 'data.attempts');
    }

    public function test_course_vr_list_marks_simulations_and_learner_results(): void
    {
        CourseVrContent::create([
            'course_id' => $this->course->id,
            'title' => '360 farm tour',
            'is_active' => true,
            'scene_json' => ['engine' => 'three-webxr'],
        ]);
        Sanctum::actingAs($this->learner);
        $this->postJson("/api/vr-studio/experiences/{$this->sim->id}/attempts", $this->attempt(true, 85))->assertOk();

        $items = collect($this->getJson("/api/courses/{$this->course->id}/vr-content")->assertOk()->json('data'))->keyBy('title');
        $this->assertSame('simulation', $items['Night oxygen crash']['kind']);
        $this->assertSame(85, $items['Night oxygen crash']['my_result']['best_score']);
        $this->assertTrue($items['Night oxygen crash']['my_result']['passed']);
        $this->assertSame('vr', $items['360 farm tour']['kind']);
        $this->assertNull($items['360 farm tour']['my_result']);
    }
    public function test_course_tutor_can_exchange_a_studio_handoff(): void
    {
        $studio = app(\App\Services\VrStudioService::class);
        $url = $studio->createHandoffUrl($this->tutor, $this->sim);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $q);

        $this->postJson('/api/vr-studio/handoff/exchange', ['token' => $q['token']])
            ->assertOk()
            ->assertJsonPath('data.experience.id', $this->sim->id);
    }
}
