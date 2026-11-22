<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseDiyContent;
use App\Models\CourseDiySubmission;
use App\Models\Enrollment;
use App\Models\User;
use App\Support\DiyTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** DIY practicals: learners tick steps, record observations with photos and hand in. */
class DiyPracticalTest extends TestCase
{
    use RefreshDatabase;

    private User $learner;
    private Course $course;
    private CourseDiyContent $diy;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $tutor = User::factory()->create(['role' => 'tutor', 'is_active' => true]);
        $this->learner = User::factory()->create(['role' => 'learner', 'is_active' => true]);
        $category = Category::create(['name' => 'Livestock', 'slug' => 'livestock']);
        $this->course = Course::create(['category_id' => $category->id, 'tutor_id' => $tutor->id, 'title' => 'Poultry', 'slug' => 'poultry', 'description' => 'Birds', 'is_published' => true]);
        Enrollment::create(['user_id' => $this->learner->id, 'course_id' => $this->course->id, 'status' => 'active', 'enrolled_at' => now()]);
        $t = DiyTemplates::all()['brood_chicks'];
        $this->diy = CourseDiyContent::create([
            'course_id' => $this->course->id,
            'tutor_id' => $tutor->id,
            'title' => $t['title'],
            'materials_needed' => $t['materials_needed'],
            'steps' => $t['steps'],
            'observation_fields' => $t['observation_fields'],
            'entries_required' => 2,
            'entry_label' => 'Day',
            'requires_review' => true,
            'is_active' => true,
        ]);
    }

    private function entry(int $n, array $extra = []): array
    {
        return array_merge(['n' => $n, 'values' => ['f0' => 32.5, 'f1' => 'Spread evenly', 'f2' => 15, 'f3' => '1', 'f5' => 'All chicks active']], $extra);
    }

    public function test_learner_sees_steps_fields_and_their_work(): void
    {
        Sanctum::actingAs($this->learner);
        $res = $this->getJson("/api/courses/{$this->course->id}/diy-content")->assertOk();
        $this->assertCount(5, $res->json('data.0.steps'));
        $this->assertSame('number', $res->json('data.0.observation_fields.0.type'));
        $this->assertSame('°C', $res->json('data.0.observation_fields.0.unit'));
        $this->assertCount(3, $res->json('data.0.observation_fields.1.options'));
        $this->assertNull($res->json('data.0.my_submission'));
    }

    public function test_steps_entries_with_photo_and_hand_in(): void
    {
        Sanctum::actingAs($this->learner);
        $this->postJson("/api/diy/{$this->diy->id}/steps", ['done' => [0, 1, 2]])->assertOk()->assertJsonPath('data.steps_done', [0, 1, 2]);

        $photo = UploadedFile::fake()->image('chicks.jpg', 640, 480);
        $this->post("/api/diy/{$this->diy->id}/entries", $this->entry(1, ['photo_f4' => $photo]), ['Accept' => 'application/json'])->assertOk();
        $sub = CourseDiySubmission::first();
        $path = $sub->entries[0]['values']['f4'];
        Storage::disk('public')->assertExists($path);
        $this->assertSame(32.5, $sub->entries[0]['values']['f0']);
        $this->assertTrue($sub->entries[0]['values']['f3']);

        // not enough entries yet
        $this->postJson("/api/diy/{$this->diy->id}/submit")->assertStatus(422);
        // editing day 1 again keeps the photo
        $this->postJson("/api/diy/{$this->diy->id}/entries", $this->entry(1, ['values' => ['f0' => 31, 'f1' => 'Huddled under the heat', 'f2' => 15]]))->assertOk();
        $this->assertSame($path, CourseDiySubmission::first()->entries[0]['values']['f4']);

        $this->postJson("/api/diy/{$this->diy->id}/entries", $this->entry(2))->assertOk();
        $this->postJson("/api/diy/{$this->diy->id}/submit")->assertOk()->assertJsonPath('data.status', 'submitted');

        $res = $this->getJson("/api/courses/{$this->course->id}/diy-content")->assertOk();
        $this->assertSame('submitted', $res->json('data.0.my_submission.status'));
        $this->assertCount(2, $res->json('data.0.my_submission.entries'));
        $this->assertStringContainsString('/storage/diy/', $res->json('data.0.my_submission.entries.0.values.f4'));
    }

    public function test_required_answers_and_valid_choices(): void
    {
        Sanctum::actingAs($this->learner);
        $this->postJson("/api/diy/{$this->diy->id}/entries", ['n' => 1, 'values' => ['f0' => 33, 'f1' => 'Not an option']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['values.f1', 'values.f2']);
        $this->postJson("/api/diy/{$this->diy->id}/entries", ['n' => 3, 'values' => []])->assertStatus(422);
    }

    public function test_no_review_needed_completes_straight_away(): void
    {
        $this->diy->update(['requires_review' => false, 'entries_required' => 1]);
        Sanctum::actingAs($this->learner);
        $this->postJson("/api/diy/{$this->diy->id}/entries", $this->entry(1))->assertOk();
        $this->postJson("/api/diy/{$this->diy->id}/submit")->assertOk()->assertJsonPath('data.status', 'approved');
    }

    public function test_only_enrolled_learners(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'learner']));
        $this->postJson("/api/diy/{$this->diy->id}/steps", ['done' => [0]])->assertForbidden();
        $this->postJson("/api/diy/{$this->diy->id}/entries", $this->entry(1))->assertForbidden();
    }
}
