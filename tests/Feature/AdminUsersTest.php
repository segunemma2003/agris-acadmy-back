<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Admin → Users: every registered account, enrolled or not, and managing them. */
class AdminUsersTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $cat = Category::create(['name' => 'Crops', 'slug' => 'crops']);
        $this->course = Course::create(['category_id' => $cat->id, 'title' => 'Rice Farming', 'slug' => 'rice', 'description' => 'x', 'is_published' => true]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->admin);
    }

    public function test_tabs_separate_enrolled_and_not_enrolled_learners(): void
    {
        $enrolled = User::factory()->cvReady()->create(['role' => 'student', 'name' => 'Enrolled Amina']);
        Enrollment::create(['user_id' => $enrolled->id, 'course_id' => $this->course->id, 'status' => 'active', 'enrolled_at' => now()]);
        $new = User::factory()->create(['role' => 'student', 'name' => 'New Bala']);
        $org = User::factory()->create(['role' => 'organisation', 'name' => 'Farm Org']);

        Livewire::test(ListUsers::class)->assertSuccessful()->assertCanSeeTableRecords([$enrolled, $new, $org, $this->admin]);
        Livewire::test(ListUsers::class, ['activeTab' => 'enrolled'])->assertCanSeeTableRecords([$enrolled])->assertCanNotSeeTableRecords([$new, $org]);
        Livewire::test(ListUsers::class, ['activeTab' => 'not_enrolled'])->assertCanSeeTableRecords([$new])->assertCanNotSeeTableRecords([$enrolled, $org]);
        Livewire::test(ListUsers::class, ['activeTab' => 'incomplete'])->assertCanSeeTableRecords([$new])->assertCanNotSeeTableRecords([$enrolled]);
        Livewire::test(ListUsers::class, ['activeTab' => 'organisations'])->assertCanSeeTableRecords([$org]);
        Livewire::test(ListUsers::class, ['activeTab' => 'staff'])->assertCanSeeTableRecords([$this->admin]);

        Livewire::test(ListUsers::class)->filterTable('course', 'none')->assertCanSeeTableRecords([$new])->assertCanNotSeeTableRecords([$enrolled]);
        Livewire::test(ListUsers::class)->filterTable('course', $this->course->id)->assertCanSeeTableRecords([$enrolled])->assertCanNotSeeTableRecords([$new]);
    }

    public function test_admin_can_enrol_activate_and_reset_password(): void
    {
        $a = User::factory()->create(['role' => 'student']);
        $b = User::factory()->create(['role' => 'student']);

        Livewire::test(ListUsers::class)->callTableAction('enrol', $a, ['course_id' => $this->course->id])->assertHasNoTableActionErrors();
        $this->assertDatabaseHas('enrollments', ['user_id' => $a->id, 'course_id' => $this->course->id, 'status' => 'active']);

        // bulk: a is already enrolled and is skipped, b is added — no duplicates
        Livewire::test(ListUsers::class)->callTableBulkAction('enrolSelected', [$a, $b], ['course_id' => $this->course->id]);
        $this->assertSame(1, Enrollment::where('user_id', $a->id)->count());
        $this->assertSame(1, Enrollment::where('user_id', $b->id)->count());

        Livewire::test(ListUsers::class)->callTableAction('toggleActive', $a);
        $this->assertFalse($a->fresh()->is_active);
        Livewire::test(ListUsers::class)->callTableBulkAction('activate', [$a]);
        $this->assertTrue($a->fresh()->is_active);

        Livewire::test(ListUsers::class)->callTableAction('setPassword', $a, ['password' => 'NewPass123', 'password_confirmation' => 'NewPass123'])->assertHasNoTableActionErrors();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewPass123', $a->fresh()->password));
    }
}
