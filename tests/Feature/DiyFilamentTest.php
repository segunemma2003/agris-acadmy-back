<?php

namespace Tests\Feature;

use App\Filament\Tutor\Resources\CourseDiyContentResource\Pages\CreateCourseDiyContent;
use App\Filament\Tutor\Resources\CourseDiyContentResource\Pages\ListCourseDiyContents;
use App\Filament\Tutor\Resources\CourseDiySubmissionResource\Pages\EditCourseDiySubmission;
use App\Filament\Tutor\Resources\CourseDiySubmissionResource\Pages\ListCourseDiySubmissions;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseDiyContent;
use App\Models\CourseDiySubmission;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Tutors build DIY practicals from a template and review learners' work in Filament. */
class DiyFilamentTest extends TestCase
{
    use RefreshDatabase;

    private User $tutor;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tutor = User::factory()->create(['role' => 'tutor', 'is_active' => true]);
        $category = Category::create(['name' => 'Livestock', 'slug' => 'livestock']);
        $this->course = Course::create(['category_id' => $category->id, 'tutor_id' => $this->tutor->id, 'title' => 'Poultry', 'slug' => 'poultry', 'description' => 'Birds', 'is_published' => true]);
        $this->actingAs($this->tutor);
        Filament::setCurrentPanel(Filament::getPanel('tutor'));
    }

    public function test_tutor_creates_a_practical_from_a_template(): void
    {
        Livewire::test(CreateCourseDiyContent::class)
            ->fillForm(['template' => 'brood_chicks'])
            ->assertFormSet(['title' => 'Brood day-old chicks for a week', 'entries_required' => 7])
            ->fillForm(['course_id' => $this->course->id])
            ->call('create')
            ->assertHasNoFormErrors();

        $diy = CourseDiyContent::first();
        $this->assertSame($this->tutor->id, $diy->tutor_id);
        $this->assertCount(5, $diy->steps);
        $this->assertCount(6, $diy->fields());
        $this->assertSame('number', $diy->fields()[0]['type']);
        $this->assertStringStartsWith('f', $diy->fields()[0]['key']);

        Livewire::test(ListCourseDiyContents::class)->assertCanSeeTableRecords([$diy]);
    }

    public function test_tutor_reviews_and_approves_work(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $diy = CourseDiyContent::create([
            'course_id' => $this->course->id, 'title' => 'Brooding', 'is_active' => true, 'entries_required' => 1,
            'steps' => [['title' => 'Warm the brooder']],
            'observation_fields' => [['key' => 'temp', 'label' => 'Temperature', 'type' => 'number', 'unit' => '°C']],
        ]);
        $sub = CourseDiySubmission::create([
            'course_diy_content_id' => $diy->id, 'course_id' => $this->course->id, 'user_id' => $learner->id,
            'steps_done' => [0], 'entries' => [['n' => 1, 'recorded_at' => now()->toIso8601String(), 'values' => ['temp' => 32]]],
            'status' => 'submitted', 'submitted_at' => now(),
        ]);

        Livewire::test(ListCourseDiySubmissions::class)->assertCanSeeTableRecords([$sub]);
        Livewire::test(EditCourseDiySubmission::class, ['record' => $sub->getRouteKey()])
            ->assertSee('Temperature')
            ->assertSee('32')
            ->fillForm(['status' => 'needs_work', 'tutor_feedback' => 'Lower the heat a little.'])
            ->call('save')
            ->assertHasNoFormErrors();
        $sub->refresh();
        $this->assertSame('needs_work', $sub->status);
        $this->assertSame($this->tutor->id, $sub->reviewed_by);
    }

    public function test_other_tutors_do_not_see_the_work(): void
    {
        $diy = CourseDiyContent::create(['course_id' => $this->course->id, 'title' => 'Brooding', 'is_active' => true]);
        $this->actingAs(User::factory()->create(['role' => 'tutor', 'is_active' => true]));
        Livewire::test(ListCourseDiyContents::class)->assertCanNotSeeTableRecords([$diy]);
    }
}
