<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Module;
use App\Models\Topic;
use App\Models\TopicTest;
use App\Models\TopicTestQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Learners can see and take every topic test of their courses from the dashboard. */
class DashboardTopicTestsTest extends TestCase
{
    use RefreshDatabase;

    private function setUpCourse(): array
    {
        $category = Category::create(['name' => 'Agri', 'slug' => 'agri']);
        $course = Course::create(['category_id' => $category->id, 'title' => 'Poultry', 'slug' => 'poultry', 'description' => 'x', 'is_published' => true]);
        $module = Module::create(['course_id' => $course->id, 'title' => 'Housing', 'sort_order' => 1, 'is_active' => true]);
        $topic = Topic::create(['module_id' => $module->id, 'title' => 'Ventilation', 'is_active' => true, 'sort_order' => 1]);
        $test = TopicTest::create([
            'topic_id' => $topic->id, 'module_id' => $module->id, 'course_id' => $course->id,
            'title' => 'Ventilation check', 'passing_score' => 50, 'max_attempts' => 2, 'is_active' => true,
        ]);
        $q1 = TopicTestQuestion::create(['topic_test_id' => $test->id, 'question' => 'Best airflow?', 'question_type' => 'multiple_choice',
            'options' => ['A' => 'None', 'B' => 'Cross-flow'], 'correct_answer' => 'B', 'explanation' => 'Air must move.', 'points' => 1, 'sort_order' => 1]);
        $q2 = TopicTestQuestion::create(['topic_test_id' => $test->id, 'question' => 'Ammonia harms birds', 'question_type' => 'true_false',
            'correct_answer' => 'true', 'points' => 1, 'sort_order' => 2]);

        // An inactive test and a test with no questions are not listed.
        TopicTest::create(['topic_id' => $topic->id, 'module_id' => $module->id, 'course_id' => $course->id, 'title' => 'Old', 'passing_score' => 50, 'is_active' => false]);
        TopicTest::create(['topic_id' => $topic->id, 'module_id' => $module->id, 'course_id' => $course->id, 'title' => 'Empty', 'passing_score' => 50, 'is_active' => true]);

        return [$course, $module, $topic, $test, $q1, $q2];
    }

    public function test_dashboard_lists_topic_tests_and_learner_can_take_them(): void
    {
        [$course, $module, $topic, $test, $q1, $q2] = $this->setUpCourse();
        $learner = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $outsider = User::factory()->create(['role' => 'student', 'is_active' => true]);
        Enrollment::create(['user_id' => $learner->id, 'course_id' => $course->id, 'status' => 'active', 'enrolled_at' => now()]);

        $this->actingAs($outsider, 'sanctum')->getJson('/api/dashboard/topic-tests')->assertOk()->assertJsonCount(0, 'data');

        $this->actingAs($learner, 'sanctum')->getJson('/api/dashboard/topic-tests')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.test_id', $test->id)
            ->assertJsonPath('data.0.topic_title', 'Ventilation')
            ->assertJsonPath('data.0.questions_count', 2)
            ->assertJsonPath('data.0.attempts_left', 2)
            ->assertJsonPath('data.0.is_passed', false);

        // The answer key is not sent before the learner answers.
        $base = "/api/courses/{$course->id}/modules/{$module->id}/topics/{$topic->id}";
        $show = $this->actingAs($learner, 'sanctum')->getJson("{$base}/test")->assertOk();
        $this->assertArrayNotHasKey('correct_answer', $show->json('data.test.questions.0'));
        $this->assertArrayNotHasKey('explanation', $show->json('data.test.questions.0'));

        // One of two right = 50%, which passes (pass mark 50); "TRUE " matches "true".
        $this->actingAs($learner, 'sanctum')->postJson("{$base}/tests/{$test->id}/submit", ['answers' => [$q1->id => 'A', $q2->id => 'TRUE ']])
            ->assertCreated()
            ->assertJsonPath('data.percentage', 50)
            ->assertJsonPath('data.is_passed', true)
            ->assertJsonPath('data.review.0.is_correct', false)
            ->assertJsonPath('data.review.0.correct_answer', 'B')
            ->assertJsonPath('data.review.0.explanation', 'Air must move.')
            ->assertJsonPath('data.review.1.is_correct', true);

        $this->actingAs($learner, 'sanctum')->getJson('/api/dashboard/topic-tests')
            ->assertJsonPath('data.0.attempts_used', 1)
            ->assertJsonPath('data.0.attempts_left', 1)
            ->assertJsonPath('data.0.best_percentage', 50)
            ->assertJsonPath('data.0.is_passed', true);

        // Max attempts is enforced.
        $this->actingAs($learner, 'sanctum')->postJson("{$base}/tests/{$test->id}/submit", ['answers' => [$q1->id => 'B', $q2->id => 'true']])->assertCreated();
        $this->actingAs($learner, 'sanctum')->postJson("{$base}/tests/{$test->id}/submit", ['answers' => [$q1->id => 'B', $q2->id => 'true']])->assertForbidden();

        // Not enrolled → cannot submit.
        $this->actingAs($outsider, 'sanctum')->postJson("{$base}/tests/{$test->id}/submit", ['answers' => [$q1->id => 'B']])->assertForbidden();
    }
}
