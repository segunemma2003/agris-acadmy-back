<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseVrContent;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Tutors can add a ready-made simulation to one of their courses straight from
 * VR Studio (no need to create a VR row in Filament first).
 */
class VrStudioAddExistingTest extends TestCase
{
    use RefreshDatabase;

    private User $tutor;
    private Course $course;
    private Course $otherCourse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tutor = User::factory()->create(['role' => 'tutor', 'is_active' => true]);
        $other = User::factory()->create(['role' => 'tutor', 'is_active' => true]);
        $category = Category::create(['name' => 'Livestock', 'slug' => 'livestock']);
        $this->course = Course::create(['category_id' => $category->id, 'tutor_id' => $this->tutor->id, 'title' => 'Poultry', 'slug' => 'poultry', 'description' => 'Birds', 'is_published' => true]);
        $this->otherCourse = Course::create(['category_id' => $category->id, 'tutor_id' => $other->id, 'title' => 'Fish', 'slug' => 'fish', 'description' => 'Ponds', 'is_published' => true]);
    }

    private function scene(): array
    {
        return ['version' => 2, 'engine' => 'sim-poultry-house', 'lesson' => ['id' => 'lesson-1', 'kit' => 'poultry_house', 'title' => 'Brooding']];
    }

    public function test_tutor_sees_only_their_courses(): void
    {
        Module::create(['course_id' => $this->course->id, 'title' => 'Week 1', 'sort_order' => 1]);
        Sanctum::actingAs($this->tutor);
        $res = $this->getJson('/api/vr-studio/courses')->assertOk();
        $this->assertSame(['Poultry'], collect($res->json('data'))->pluck('title')->all());
        $this->assertSame('Week 1', $res->json('data.0.modules.0.title'));
    }

    public function test_tutor_adds_a_ready_made_simulation_and_publishes_it(): void
    {
        Sanctum::actingAs($this->tutor);
        $res = $this->postJson('/api/vr-studio/experiences', [
            'course_id' => $this->course->id,
            'title' => 'Brooding day-old chicks',
            'cta_label' => 'Start simulation',
            'scene' => $this->scene(),
            'publish' => true,
        ])->assertCreated();

        $row = CourseVrContent::findOrFail($res->json('data.id'));
        $this->assertSame('published', $row->studio_status);
        $this->assertSame($this->tutor->id, $row->tutor_id);
        $this->assertTrue($row->isSimulation());
        $this->assertNotEmpty($row->vr_url);
        $this->assertSame('sim-poultry-house', $row->scene_json['engine']);
    }

    public function test_draft_is_listed_for_editing(): void
    {
        Sanctum::actingAs($this->tutor);
        $id = $this->postJson('/api/vr-studio/experiences', ['course_id' => $this->course->id, 'title' => 'Heat stress', 'scene' => $this->scene()])
            ->assertCreated()->json('data.id');
        $this->assertSame('draft', CourseVrContent::find($id)->studio_status);
        $this->getJson('/api/vr-studio/experiences')->assertOk()->assertJsonFragment(['id' => $id]);
    }

    public function test_cannot_add_to_someone_elses_course(): void
    {
        Sanctum::actingAs($this->tutor);
        $this->postJson('/api/vr-studio/experiences', ['course_id' => $this->otherCourse->id, 'title' => 'X', 'scene' => $this->scene()])->assertForbidden();
        $this->assertSame(0, CourseVrContent::count());
    }

    public function test_module_must_belong_to_the_course(): void
    {
        $foreign = Module::create(['course_id' => $this->otherCourse->id, 'title' => 'Other', 'sort_order' => 1]);
        Sanctum::actingAs($this->tutor);
        $this->postJson('/api/vr-studio/experiences', ['course_id' => $this->course->id, 'module_id' => $foreign->id, 'title' => 'X'])->assertStatus(422);
    }

    public function test_learners_cannot_create(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'learner']));
        $this->postJson('/api/vr-studio/experiences', ['course_id' => $this->course->id, 'title' => 'X'])->assertForbidden();
        $this->getJson('/api/vr-studio/courses')->assertForbidden();
    }
}
