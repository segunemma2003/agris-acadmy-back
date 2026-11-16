<?php

namespace Tests\Feature;

use App\Jobs\BuildLearnerCvJob;
use App\Models\Apprenticeship;
use App\Models\ApprenticeshipSlot;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\JobMatch;
use App\Models\LearnerCv;
use App\Models\Organisation;
use App\Models\User;
use App\Services\CvService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JobMatchingTest extends TestCase
{
    use RefreshDatabase;

    private Course $poultry;
    private Course $fish;
    private User $employer;
    private Organisation $org;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Notification::fake();
        Queue::fake();
        Storage::fake('local');

        $cat = Category::create(['name' => 'Livestock', 'slug' => 'livestock']);
        $fishCat = Category::create(['name' => 'Aquaculture', 'slug' => 'aquaculture']);
        $this->poultry = Course::create(['category_id' => $cat->id, 'title' => 'Broiler Poultry Production', 'slug' => 'broilers', 'description' => 'x', 'is_published' => true, 'level' => 'beginner']);
        $this->fish = Course::create(['category_id' => $fishCat->id, 'title' => 'Catfish Pond Management', 'slug' => 'catfish', 'description' => 'x', 'is_published' => true]);

        $this->employer = User::factory()->create(['role' => 'organisation', 'is_active' => true]);
        $this->org = Organisation::create(['user_id' => $this->employer->id, 'name' => 'Kano Poultry Ltd', 'is_approved' => true, 'country' => 'Nigeria']);
    }

    private function graduate(string $name, Course $course, string $state = 'Kano', array $attrs = []): User
    {
        $u = User::factory()->create($attrs + ['role' => 'student', 'is_active' => true, 'name' => $name, 'state' => $state]);
        Enrollment::create(['user_id' => $u->id, 'course_id' => $course->id, 'status' => 'completed', 'progress_percentage' => 100, 'enrolled_at' => now(), 'completed_at' => now()]);
        app(CvService::class)->build($u);
        return $u;
    }

    private function slot(array $attrs = []): ApprenticeshipSlot
    {
        return ApprenticeshipSlot::create($attrs + [
            'organisation_id' => $this->org->id, 'title' => 'Poultry farm attendant',
            'description' => 'Care for broilers and layers, record keeping', 'state' => 'Kano', 'duration' => '6 months', 'openings' => 2, 'is_active' => true,
        ]);
    }

    public function test_completing_a_course_queues_a_cv_build_and_cv_has_courses_and_pdf(): void
    {
        $u = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $e = Enrollment::create(['user_id' => $u->id, 'course_id' => $this->poultry->id, 'status' => 'active', 'enrolled_at' => now()]);
        $e->update(['status' => 'completed', 'completed_at' => now(), 'progress_percentage' => 100]);
        Queue::assertPushed(BuildLearnerCvJob::class, fn ($job) => $job->userId === $u->id);

        $cv = app(CvService::class)->build($u);
        $this->assertSame('Broiler Poultry Production', $cv->data['courses'][0]['title']);
        $this->assertTrue($cv->open_to_work);
        Storage::disk('local')->assertExists($cv->pdf_path);

        $this->actingAs($u, 'sanctum')->get('/api/cv/pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_matching_ranks_relevant_local_graduates_and_hides_contact(): void
    {
        $best = $this->graduate('Amina Poultry Kano', $this->poultry, 'Kano', ['main_work' => 'unemployed_seeking']);
        $far = $this->graduate('Bola Poultry Lagos', $this->poultry, 'Lagos');
        $this->graduate('Chidi Fish Kano', $this->fish, 'Kano');
        $hidden = $this->graduate('Dayo Hidden', $this->poultry, 'Kano');
        LearnerCv::where('user_id', $hidden->id)->update(['open_to_work' => false]);

        $slot = $this->slot();
        $res = $this->actingAs($this->employer, 'sanctum')->getJson("/api/org/slots/{$slot->id}/matches")->assertOk()->json('data.matches');
        $names = array_column(array_column($res, 'candidate'), 'name');

        $this->assertSame('Amina Poultry Kano', $names[0]);
        $this->assertContains('Bola Poultry Lagos', $names);
        $this->assertNotContains('Dayo Hidden', $names);
        $this->assertGreaterThan($res[array_search('Bola Poultry Lagos', $names)]['score'], $res[0]['score']);
        $this->assertNotEmpty($res[0]['reasons']);
        $this->assertArrayNotHasKey('contact', $res[0]['cv']);
        if (in_array('Chidi Fish Kano', $names, true)) {
            $this->assertLessThan($res[0]['score'], $res[array_search('Chidi Fish Kano', $names)]['score']);
        }
    }

    public function test_required_course_is_a_must(): void
    {
        $this->graduate('Amina', $this->poultry);
        $this->graduate('Chidi', $this->fish);
        $slot = $this->slot(['required_course_id' => $this->fish->id, 'title' => 'Hatchery assistant', 'description' => 'fish hatchery']);

        $names = array_column(array_column(
            $this->actingAs($this->employer, 'sanctum')->getJson("/api/org/slots/{$slot->id}/matches")->json('data.matches'), 'candidate'), 'name');
        $this->assertSame(['Chidi'], $names);
    }

    public function test_employer_selects_learner_accepts_and_placement_is_created(): void
    {
        $amina = $this->graduate('Amina', $this->poultry);
        $slot = $this->slot();
        $this->actingAs($this->employer, 'sanctum')->getJson("/api/org/slots/{$slot->id}/matches")->assertOk();
        $match = JobMatch::where('user_id', $amina->id)->firstOrFail();

        // Another organisation can't act on it
        $other = User::factory()->create(['role' => 'organisation', 'is_active' => true]);
        Organisation::create(['user_id' => $other->id, 'name' => 'Other', 'is_approved' => true]);
        $this->app['auth']->forgetGuards();
        $this->actingAs($other, 'sanctum')->postJson("/api/org/matches/{$match->id}/select")->assertForbidden();

        $this->app['auth']->forgetGuards();
        $this->actingAs($this->employer, 'sanctum')->postJson("/api/org/matches/{$match->id}/select")->assertOk();

        $this->app['auth']->forgetGuards();
        $offers = $this->actingAs($amina, 'sanctum')->getJson('/api/career/offers')->assertOk()->json('data');
        $this->assertSame('selected', $offers[0]['status']);
        $this->assertSame('Kano Poultry Ltd', $offers[0]['slot']['organisation']['name']);

        $this->postJson("/api/career/offers/{$match->id}/respond", ['response' => 'accept'])->assertOk();
        $this->assertSame('accepted', $match->fresh()->status);
        $this->assertSame('accepted', Apprenticeship::where('user_id', $amina->id)->value('status'));

        // Employer now sees contact details
        $this->app['auth']->forgetGuards();
        $res = $this->actingAs($this->employer, 'sanctum')->getJson("/api/org/slots/{$slot->id}/matches")->json('data.matches');
        $this->assertSame($amina->email, $res[0]['cv']['contact']['email']);
    }

    public function test_unapproved_organisation_cannot_see_matches(): void
    {
        $this->org->update(['is_approved' => false]);
        $slot = $this->slot();
        $this->actingAs($this->employer, 'sanctum')->getJson("/api/org/slots/{$slot->id}/matches")->assertForbidden();
    }
}
