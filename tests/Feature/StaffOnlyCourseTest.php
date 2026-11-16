<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StaffOnlyCourseTest extends TestCase
{
    use RefreshDatabase;

    private Course $public;
    private Course $staffOnly;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Notification::fake();
        Queue::fake();

        $this->category = Category::create(['name' => 'Agri', 'slug' => 'agri', 'is_active' => true]);
        $this->public = Course::create([
            'category_id' => $this->category->id, 'title' => 'Rice Farming', 'slug' => 'rice-farming',
            'description' => 'Intro', 'is_published' => true, 'is_featured' => true,
        ]);
        $this->staffOnly = Course::create([
            'category_id' => $this->category->id, 'title' => 'Staff Induction', 'slug' => 'staff-induction',
            'description' => 'Internal', 'is_published' => true, 'is_featured' => true, 'is_staff_only' => true,
        ]);
    }

    private function titles(string $url): array
    {
        $res = $this->getJson($url)->assertOk()->json('data');
        $items = $res['data'] ?? $res; // paginated or plain list
        return collect($items)->pluck('title')->all();
    }

    private function learner(array $attrs = []): User
    {
        return User::factory()->create($attrs + ['role' => 'student', 'is_active' => true]);
    }

    public function test_guests_and_learners_do_not_see_staff_only_courses(): void
    {
        // Guest
        $this->assertSame(['Rice Farming'], $this->titles('/api/courses'));
        $this->getJson("/api/courses/{$this->staffOnly->id}")->assertNotFound();
        $this->assertNotContains('Staff Induction', $this->titles('/api/featured-courses'));
        $cats = $this->getJson('/api/categories-with-courses')->assertOk()->json('data');
        $this->assertSame(['Rice Farming'], collect($cats[0]['courses'])->pluck('title')->all());

        // Signed-in learner
        $this->actingAs($this->learner(), 'sanctum');
        $this->assertSame(['Rice Farming'], $this->titles('/api/courses'));
        $this->getJson("/api/courses/{$this->staffOnly->id}")->assertNotFound();
        $this->postJson('/api/enroll', ['course_id' => $this->staffOnly->id])->assertStatus(400);
        $this->assertNotContains('Staff Induction', $this->titles('/api/featured-courses'));
    }

    public function test_marked_staff_and_staff_roles_see_staff_only_courses(): void
    {
        foreach ([$this->learner(['is_staff' => true]), User::factory()->create(['role' => 'tutor', 'is_active' => true])] as $user) {
            $this->app['auth']->forgetGuards();
            $this->actingAs($user, 'sanctum');
            $this->assertEqualsCanonicalizing(['Rice Farming', 'Staff Induction'], $this->titles('/api/courses'));
            $this->getJson("/api/courses/{$this->staffOnly->id}")->assertOk();
            $this->assertContains('Staff Induction', $this->titles('/api/featured-courses'));
        }

        // Marked staff can enroll
        $this->app['auth']->forgetGuards();
        $staff = $this->learner(['is_staff' => true]);
        $this->actingAs($staff, 'sanctum');
        $this->postJson('/api/enroll', ['course_id' => $this->staffOnly->id])->assertSuccessful();
    }

    public function test_is_staff_rules(): void
    {
        $this->assertFalse($this->learner()->isStaff());
        $this->assertTrue($this->learner(['is_staff' => true])->isStaff());
        $this->assertFalse($this->learner(['is_staff' => true, 'is_active' => false])->isStaff());
        foreach (['admin', 'tutor', 'facilitator'] as $role) {
            $this->assertTrue(User::factory()->create(['role' => $role, 'is_active' => true])->isStaff(), $role);
        }
        foreach (['partner', 'organisation', 'tagdev'] as $role) {
            $this->assertFalse(User::factory()->create(['role' => $role, 'is_active' => true])->isStaff(), $role);
        }
    }
}
