<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LearnerInterventionAlert;
use App\Models\Module;
use App\Models\StudentProgress;
use App\Models\Topic;
use App\Models\User;
use App\Services\LearnerInterventionAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LearnerAlertsTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;
    private Topic $topic;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Notification::fake();
        Queue::fake();

        $category = Category::create(['name' => 'Agri', 'slug' => 'agri']);
        $this->course = Course::create([
            'category_id' => $category->id, 'title' => 'Rice Farming', 'slug' => 'rice-farming',
            'description' => 'Intro', 'is_published' => true,
        ]);
        $module = Module::create(['course_id' => $this->course->id, 'title' => 'M1', 'sort_order' => 1, 'is_active' => true]);
        $this->topic = Topic::create(['module_id' => $module->id, 'title' => 'T1', 'sort_order' => 1, 'is_active' => true]);
    }

    /** An active learner (logged in today, so not "inactive") enrolled $daysAgo with $progress %. */
    private function enrolled(int $daysAgo, float $progress): User
    {
        $user = User::factory()->create([
            'role' => 'student', 'is_active' => true, 'last_active_date' => now(), 'last_login_at' => now(),
        ]);
        Enrollment::create([
            'user_id' => $user->id, 'course_id' => $this->course->id, 'status' => 'active',
            'enrolled_at' => now()->subDays($daysAgo), 'progress_percentage' => $progress,
        ]);
        return $user;
    }

    private function progress(User $user, int $daysAgo): void
    {
        StudentProgress::create([
            'user_id' => $user->id, 'course_id' => $this->course->id, 'topic_id' => $this->topic->id,
            'is_completed' => true, 'last_accessed_at' => now()->subDays($daysAgo),
        ]);
    }

    private function reasonsFor(User $user): array
    {
        return LearnerInterventionAlert::open()->where('user_id', $user->id)->pluck('reason')->sort()->values()->all();
    }

    public function test_detects_stalled_and_never_started_learners(): void
    {
        $stalled = $this->enrolled(30, 40);
        $this->progress($stalled, 20);

        $onTrack = $this->enrolled(30, 40);
        $this->progress($onTrack, 2);

        $neverStarted = $this->enrolled(10, 0);
        $justJoined = $this->enrolled(2, 0);

        $result = app(LearnerInterventionAlertService::class)->runNightly();

        $this->assertSame(['stalled_course'], $this->reasonsFor($stalled));
        $this->assertSame([], $this->reasonsFor($onTrack));
        $this->assertSame(['never_started'], $this->reasonsFor($neverStarted));
        $this->assertSame([], $this->reasonsFor($justJoined));
        $this->assertSame(2, $result['emailed']);
    }

    public function test_alert_auto_resolves_when_learner_reengages_and_reopens_if_risk_returns(): void
    {
        $user = $this->enrolled(30, 40);
        $this->progress($user, 20);
        $service = app(LearnerInterventionAlertService::class);

        $service->runNightly();
        $alert = LearnerInterventionAlert::where('user_id', $user->id)->firstOrFail();
        $this->assertNull($alert->resolved_at);

        // Learner comes back
        StudentProgress::where('user_id', $user->id)->update(['last_accessed_at' => now()]);
        $result = $service->runNightly();
        $alert->refresh();
        $this->assertNotNull($alert->resolved_at);
        $this->assertTrue($alert->auto_resolved);
        $this->assertSame(1, $result['resolved']);

        // Stalls again → reopened and re-emailed
        StudentProgress::where('user_id', $user->id)->update(['last_accessed_at' => now()->subDays(20)]);
        $result = $service->runNightly();
        $alert->refresh();
        $this->assertNull($alert->resolved_at);
        $this->assertSame(1, $result['emailed']);
    }

    public function test_staff_resolution_holds_for_a_week(): void
    {
        $user = $this->enrolled(10, 0);
        $service = app(LearnerInterventionAlertService::class);
        $service->runNightly();

        $alert = LearnerInterventionAlert::where('user_id', $user->id)->firstOrFail();
        $alert->forceFill(['resolved_at' => now(), 'resolution_note' => 'Called them'])->save();

        $service->runNightly(); // still never started, but staff just handled it
        $this->assertNotNull($alert->refresh()->resolved_at);

        $alert->forceFill(['resolved_at' => now()->subDays(8)])->save();
        $service->runNightly(); // a week on and still not started → reopened
        $this->assertNull($alert->refresh()->resolved_at);
    }

    public function test_partner_dashboard_shows_at_risk_counts(): void
    {
        $this->enrolled(10, 0);
        app(LearnerInterventionAlertService::class)->runNightly();

        $partner = User::factory()->create(['role' => 'partner', 'is_active' => true, 'dashboard_permissions' => ['engagement']]);
        $stats = collect($this->actingAs($partner, 'sanctum')->getJson('/api/partner/dashboard')->assertOk()
            ->json('data.engagement.early_warning'))->keyBy('key');

        $this->assertSame(1, $stats['learners_at_risk']['value']);
        $this->assertSame(1, $stats['at_risk_never_started']['value']);
    }
}
