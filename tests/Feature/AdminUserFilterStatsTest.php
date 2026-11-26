<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Widgets\UserFilterStats;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUserFilterStatsTest extends TestCase
{
    use RefreshDatabase;

    private User $old;
    private User $recent;
    private User $today;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true, 'created_at' => now()->subYears(2)]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($admin);

        $cat = Category::create(['name' => 'Crops', 'slug' => 'crops']);
        $course = Course::create(['category_id' => $cat->id, 'title' => 'Rice', 'slug' => 'rice', 'description' => 'x', 'is_published' => true]);
        $this->old = User::factory()->create(['role' => 'student', 'created_at' => now()->subMonths(6)]);
        $this->recent = User::factory()->create(['role' => 'student', 'created_at' => now()->subDays(10), 'last_login_at' => now()->subDay()]);
        $this->today = User::factory()->create(['role' => 'student', 'created_at' => now()]);
        Enrollment::create(['user_id' => $this->recent->id, 'course_id' => $course->id, 'status' => 'completed', 'enrolled_at' => now()]);
    }

    public function test_registered_period_filters_from_a_date_up_to_now(): void
    {
        Livewire::test(ListUsers::class)
            ->filterTable('registered', ['period' => '30d'])
            ->assertCanSeeTableRecords([$this->recent, $this->today])
            ->assertCanNotSeeTableRecords([$this->old]);

        Livewire::test(ListUsers::class)
            ->filterTable('registered', ['period' => 'custom', 'from' => now()->subDays(20)->toDateString(), 'until' => null])
            ->assertCanSeeTableRecords([$this->recent, $this->today])
            ->assertCanNotSeeTableRecords([$this->old]);

        Livewire::test(ListUsers::class)
            ->filterTable('registered', ['period' => 'today'])
            ->assertCanSeeTableRecords([$this->today])
            ->assertCanNotSeeTableRecords([$this->recent, $this->old]);
    }

    public function test_state_filter_takes_several_states(): void
    {
        $this->old->update(['state' => 'Oyo']);
        $this->recent->update(['state' => 'Kano']);
        $this->today->update(['state' => 'Lagos']);

        Livewire::test(ListUsers::class)
            ->filterTable('state', ['Oyo', 'Kano'])
            ->assertCanSeeTableRecords([$this->old, $this->recent])
            ->assertCanNotSeeTableRecords([$this->today]);

        Livewire::test(UserFilterStats::class, ['tableFilters' => ['state' => ['values' => ['Oyo', 'Kano']]], 'activeTab' => null, 'tableSearch' => null])
            ->assertSee('Matching your 1 filter')
            ->assertSeeInOrder(['Users', '2']);

        Livewire::test(UserFilterStats::class, ['tableFilters' => ['state' => ['values' => []]], 'activeTab' => null, 'tableSearch' => null])
            ->assertSee('No filters applied');
    }

    public function test_stats_and_export_follow_the_filters(): void
    {
        $filters = ['registered' => ['period' => '30d', 'from' => null, 'until' => null]];

        Livewire::test(UserFilterStats::class, ['tableFilters' => $filters, 'activeTab' => null, 'tableSearch' => null])
            ->assertSeeInOrder(['Users', '2', 'Learners', '2', 'Enrolled in a course', '1', 'Completed a course', '1'])
            ->assertSee('Signed in, last 30 days');

        Livewire::test(UserFilterStats::class, ['tableFilters' => [], 'activeTab' => null, 'tableSearch' => null])
            ->assertSee('No filters applied')
            ->assertSeeInOrder(['Users', '4']);

        $page = Livewire::test(ListUsers::class)->filterTable('registered', ['period' => '30d']);
        $this->assertSame(2, $page->instance()->getTableQueryForExport()->count());
        $page->mountTableAction('export')->assertSee('Export 2 users');
    }
}
