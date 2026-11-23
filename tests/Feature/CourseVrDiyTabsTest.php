<?php

namespace Tests\Feature;

use App\Filament\Resources\CourseResource\Pages\ViewCourse;
use App\Filament\Shared\RelationManagers\DiyContentRelationManager;
use App\Filament\Shared\RelationManagers\VrContentRelationManager;
use App\Filament\Tutor\Resources\CourseResource\Pages\ViewCourse as TutorViewCourse;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseDiyContent;
use App\Models\CourseVrContent;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** VR & Simulations and DIY Practicals tabs on a course page, for admins and tutors. */
class CourseVrDiyTabsTest extends TestCase
{
    use RefreshDatabase;

    private User $tutor;
    private Course $course;
    private CourseVrContent $sim;
    private CourseDiyContent $diy;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.env' => 'local']);
        $this->tutor = User::factory()->create(['role' => 'tutor', 'is_active' => true]);
        $category = Category::create(['name' => 'Livestock', 'slug' => 'livestock']);
        $this->course = Course::create(['category_id' => $category->id, 'tutor_id' => $this->tutor->id, 'title' => 'Poultry', 'slug' => 'poultry', 'description' => 'Birds']);
        $this->sim = CourseVrContent::create([
            'course_id' => $this->course->id, 'title' => 'Layer house day', 'studio_status' => 'published',
            'studio_slug' => 'sim-layers', 'is_active' => true,
            'scene_json' => ['version' => 2, 'engine' => 'sim-poultry-house', 'lesson' => ['id' => 'l', 'title' => 'L']],
        ]);
        $this->diy = CourseDiyContent::create(['course_id' => $this->course->id, 'title' => 'Brooding chicks', 'is_active' => true]);
    }

    public function test_tutor_sees_hides_and_removes_from_the_course_page(): void
    {
        $this->actingAs($this->tutor);
        Filament::setCurrentPanel(Filament::getPanel('tutor'));

        $this->get(\App\Filament\Tutor\Resources\CourseResource::getUrl('view', ['record' => $this->course]))->assertOk();

        Livewire::test(VrContentRelationManager::class, ['ownerRecord' => $this->course, 'pageClass' => TutorViewCourse::class])
            ->assertCanSeeTableRecords([$this->sim])
            ->assertSee('Poultry simulation')
            ->callTableAction('toggle', $this->sim)
            ->callTableAction(DeleteAction::class, $this->sim);
        $this->assertDatabaseMissing('course_vr_content', ['id' => $this->sim->id]);

        Livewire::test(DiyContentRelationManager::class, ['ownerRecord' => $this->course, 'pageClass' => TutorViewCourse::class])
            ->assertCanSeeTableRecords([$this->diy])
            ->callTableAction('toggle', $this->diy);
        $this->assertFalse($this->diy->fresh()->is_active);

        // "Add" pre-fills the course
        $this->get(\App\Filament\Tutor\Resources\CourseDiyContentResource::getUrl('create', ['course_id' => $this->course->id]))->assertOk();
        $this->get(\App\Filament\Tutor\Resources\CourseVrContentResource::getUrl('create', ['course_id' => $this->course->id]))->assertOk();
    }

    public function test_admin_sees_both_tabs(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'is_active' => true]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->get(\App\Filament\Resources\CourseResource::getUrl('view', ['record' => $this->course]))->assertOk();

        Livewire::test(VrContentRelationManager::class, ['ownerRecord' => $this->course, 'pageClass' => ViewCourse::class])
            ->assertCanSeeTableRecords([$this->sim])
            ->callTableAction('toggle', $this->sim);
        $this->assertFalse($this->sim->fresh()->is_active);

        Livewire::test(DiyContentRelationManager::class, ['ownerRecord' => $this->course, 'pageClass' => ViewCourse::class])
            ->assertCanSeeTableRecords([$this->diy])
            ->assertTableActionExists('review');

        $this->get(\App\Filament\Resources\CourseVrContentResource::getUrl('create', ['course_id' => $this->course->id]))->assertOk();
        $this->get(\App\Filament\Resources\CourseDiyContentResource::getUrl('create', ['course_id' => $this->course->id]))->assertOk();
    }
}
