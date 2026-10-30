<?php

namespace Tests\Feature;

use App\Filament\Resources\CourseResource;
use App\Filament\Resources\CourseResource\Pages\ViewCourse;
use App\Filament\Resources\CourseResource\RelationManagers as CourseRM;
use App\Filament\Resources\ModuleResource;
use App\Filament\Resources\ModuleResource\Pages\EditModule;
use App\Filament\Resources\ModuleResource\RelationManagers as ModuleRM;
use App\Filament\Resources\ModuleTestResource;
use App\Filament\Resources\ModuleTestResource\Pages\EditModuleTest;
use App\Filament\Resources\ModuleTestResource\RelationManagers\QuestionsRelationManager as ModuleQuestionsRM;
use App\Filament\Resources\TopicResource;
use App\Filament\Resources\TopicResource\Pages\EditTopic;
use App\Filament\Resources\TopicResource\RelationManagers as TopicRM;
use App\Filament\Resources\TopicTestResource;
use App\Filament\Resources\TopicTestResource\Pages\EditTopicTest;
use App\Filament\Resources\TopicTestResource\RelationManagers\QuestionsRelationManager as TopicQuestionsRM;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Admin can build a course's content from the course page, drilling down
 * Course → Module → Topic → Test → Questions, plus assignments at each level.
 */
class AdminCourseContentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $tutor;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        // User isn't a FilamentUser, so Filament only admits panel users when
        // APP_ENV is "local" (as on the deployed servers) — mirror that here.
        config(['app.env' => 'local']);
        Queue::fake();
        Mail::fake();
        Notification::fake();

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->tutor = User::factory()->create(['role' => 'tutor', 'is_active' => true]);
        $category = Category::create(['name' => 'Agri', 'slug' => 'agri']);
        $this->course = Course::create([
            'category_id' => $category->id,
            'tutor_id' => $this->tutor->id,
            'title' => 'Poultry Basics',
            'slug' => 'poultry-basics',
            'description' => 'Intro',
        ]);

        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admin_builds_course_content_by_drilling_down(): void
    {
        // Course page (view) shows; Modules tab has "Add module".
        $this->get(CourseResource::getUrl('view', ['record' => $this->course]))->assertOk();

        Livewire::test(CourseRM\ModulesRelationManager::class, ['ownerRecord' => $this->course, 'pageClass' => ViewCourse::class])
            ->assertTableActionExists(CreateAction::class)
            ->callTableAction(CreateAction::class, data: ['title' => 'Housing', 'sort_order' => 1, 'is_active' => true])
            ->assertHasNoTableActionErrors();
        $module = $this->course->modules()->firstOrFail();
        $this->assertSame('Housing', $module->title);
        $this->assertSame($this->tutor->id, $module->tutor_id, 'module stays owned by the course tutor');

        // Course-level assignment tied to the module.
        Livewire::test(CourseRM\AssignmentsRelationManager::class, ['ownerRecord' => $this->course, 'pageClass' => ViewCourse::class])
            ->callTableAction(CreateAction::class, data: ['title' => 'Coop plan', 'description' => 'Draw it', 'max_score' => 100, 'module_id' => $module->id])
            ->assertHasNoTableActionErrors();
        $this->assertDatabaseHas('assignments', ['title' => 'Coop plan', 'course_id' => $this->course->id, 'module_id' => $module->id, 'tutor_id' => $this->tutor->id]);

        // Module page → topics, module test, module assignment.
        $this->get(ModuleResource::getUrl('edit', ['record' => $module]))->assertOk();

        Livewire::test(ModuleRM\TopicsRelationManager::class, ['ownerRecord' => $module, 'pageClass' => EditModule::class])
            ->callTableAction(CreateAction::class, data: ['title' => 'Ventilation', 'content_type' => 'video', 'sort_order' => 1])
            ->assertHasNoTableActionErrors();
        $topic = $module->topics()->firstOrFail();

        Livewire::test(ModuleRM\TestsRelationManager::class, ['ownerRecord' => $module, 'pageClass' => EditModule::class])
            ->callTableAction(CreateAction::class, data: ['title' => 'Housing quiz', 'passing_score' => 80, 'time_limit_minutes' => 30])
            ->assertHasNoTableActionErrors();
        $moduleTest = $module->test()->firstOrFail();
        $this->assertSame($this->course->id, $moduleTest->course_id);

        Livewire::test(ModuleRM\AssignmentsRelationManager::class, ['ownerRecord' => $module, 'pageClass' => EditModule::class])
            ->callTableAction(CreateAction::class, data: ['title' => 'Module task', 'description' => 'Do it', 'max_score' => 50, 'topic_id' => $topic->id])
            ->assertHasNoTableActionErrors();
        $this->assertDatabaseHas('assignments', ['title' => 'Module task', 'course_id' => $this->course->id, 'module_id' => $module->id, 'topic_id' => $topic->id]);

        // Topic page → topic test, topic assignment.
        $this->get(TopicResource::getUrl('edit', ['record' => $topic]))->assertOk();

        Livewire::test(TopicRM\TestsRelationManager::class, ['ownerRecord' => $topic, 'pageClass' => EditTopic::class])
            ->callTableAction(CreateAction::class, data: ['title' => 'Ventilation check', 'passing_score' => 70, 'time_limit_minutes' => 10])
            ->assertHasNoTableActionErrors();
        $topicTest = $topic->test()->firstOrFail();
        $this->assertSame([$module->id, $this->course->id], [$topicTest->module_id, $topicTest->course_id]);

        Livewire::test(TopicRM\AssignmentsRelationManager::class, ['ownerRecord' => $topic, 'pageClass' => EditTopic::class])
            ->callTableAction(CreateAction::class, data: ['title' => 'Classwork', 'description' => 'In class', 'max_score' => 10])
            ->assertHasNoTableActionErrors();
        $this->assertDatabaseHas('assignments', ['title' => 'Classwork', 'course_id' => $this->course->id, 'module_id' => $module->id, 'topic_id' => $topic->id]);

        // Test pages → questions.
        $this->get(ModuleTestResource::getUrl('edit', ['record' => $moduleTest]))->assertOk();
        $this->get(TopicTestResource::getUrl('edit', ['record' => $topicTest]))->assertOk();

        // Deterministic repeater item keys so test data replaces the default empty options.
        $undoRepeaterFake = Repeater::fake();
        Livewire::test(ModuleQuestionsRM::class, ['ownerRecord' => $moduleTest, 'pageClass' => EditModuleTest::class])
            ->callTableAction(CreateAction::class, data: [
                'question_type' => 'multiple_choice',
                'question' => 'Best roof?',
                'options' => [['option' => 'Zinc'], ['option' => 'Thatch'], ['option' => 'Tarp'], ['option' => 'None']],
                'correct_answer' => 'B',
                'points' => 1,
            ])
            ->assertHasNoTableActionErrors();
        $question = $moduleTest->questions()->firstOrFail();
        $this->assertSame(['A' => 'Zinc', 'B' => 'Thatch', 'C' => 'Tarp', 'D' => 'None'], $question->options);

        // Editing keeps options in the stored {"A": ...} shape.
        Livewire::test(ModuleQuestionsRM::class, ['ownerRecord' => $moduleTest, 'pageClass' => EditModuleTest::class])
            ->callTableAction(EditAction::class, $question, data: ['question' => 'Best roof material?'])
            ->assertHasNoTableActionErrors();
        $question->refresh();
        $this->assertSame('Best roof material?', $question->question);
        $this->assertSame(['A' => 'Zinc', 'B' => 'Thatch', 'C' => 'Tarp', 'D' => 'None'], $question->options);

        Livewire::test(TopicQuestionsRM::class, ['ownerRecord' => $topicTest, 'pageClass' => EditTopicTest::class])
            ->callTableAction('bulk_create', data: ['questions' => [
                ['question_type' => 'true_false', 'question' => 'Air flow matters', 'correct_answer' => 'true', 'points' => 1],
                ['question_type' => 'multiple_choice', 'question' => 'Pick one', 'option_a' => 'X', 'option_b' => 'Y', 'correct_answer' => 'A', 'points' => 2],
            ]])
            ->assertHasNoTableActionErrors();
        $undoRepeaterFake();
        // The 3 untouched default rows are skipped, not saved or rejected.
        $this->assertSame(2, $topicTest->questions()->count());
        $this->assertSame(['A' => 'X', 'B' => 'Y'], $topicTest->questions()->where('question', 'Pick one')->first()->options);
    }
}
