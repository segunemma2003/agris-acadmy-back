<?php

namespace Tests\Feature;

use App\Filament\Resources\CourseOutcomeSurveyResource;
use App\Filament\Resources\EnrollmentResource;
use App\Filament\Resources\UserResource;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Participant registration form: consent + Parts 1–3 on the profile, Part 4
 * on enrollment, Part 5 end-of-course check at completion and six months
 * later — and admins can see all of it.
 */
class ParticipantFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.env' => 'local']); // see AdminCourseContentTest
        Queue::fake();
        Mail::fake();
        Notification::fake();
    }

    public function test_participant_form_end_to_end(): void
    {
        $category = Category::create(['name' => 'Agri', 'slug' => 'agri']);
        $course = Course::create([
            'category_id' => $category->id, 'title' => 'Rice Farming', 'slug' => 'rice-farming',
            'description' => 'Intro', 'is_published' => true,
        ]);

        // Register: consent + Parts 1–3
        $res = $this->postJson('/api/register', [
            'first_name' => 'Aisha', 'surname' => 'Bello',
            'email' => 'aisha@example.com', 'password' => 'password123', 'password_confirmation' => 'password123',
            'phone' => '08031234567', 'gender' => 'female', 'date_of_birth' => '2001-04-02',
            'consent' => true,
            'nationality' => 'nigerian', 'has_disability' => 'yes', 'disability_types' => ['seeing', 'hearing'],
            'displacement_status' => 'idp', 'women_category' => 'young_mother', 'education_level' => 'secondary',
            'state' => 'Kano', 'lga' => 'Dala', 'locality_type' => 'peri_urban',
            'learner_category' => 'farmer', 'main_work' => 'self_employed', 'job_title' => 'Rice farmer',
            'employment_type' => 'seasonal', 'work_started_month' => 3, 'work_started_year' => 2022,
            'work_organisation' => 'Anadariya Farms Kano', 'work_sector' => 'primary', 'main_commodity' => 'rice',
            'income_band' => 'under_30000', 'value_chain' => 'production',
        ])->assertCreated()->json('data');
        $user = User::where('email', 'aisha@example.com')->firstOrFail();
        $this->assertSame('Aisha Bello', $user->name);
        $this->assertNotNull($user->consent_given_at);
        $this->assertSame(['seeing', 'hearing'], $user->disability_types);
        $this->assertSame('rice', $res['user']['main_commodity']);
        $this->assertNotNull($res['user']['consent_given_at']);

        // Other clients that don't send consent can still register
        $this->postJson('/api/register', ['name' => 'No Consent', 'email' => 'nc@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'gender' => 'male', 'date_of_birth' => '1999-01-01', 'has_disability' => 'no', 'displacement_status' => 'none'])->assertCreated();

        // Gender, date of birth, disability and IDP status are required
        $this->postJson('/api/register', ['name' => 'Missing', 'email' => 'missing@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123'])
            ->assertStatus(422)->assertJsonValidationErrors(['gender', 'date_of_birth', 'has_disability', 'displacement_status']);
        $this->assertNull(User::where('email', 'nc@example.com')->value('consent_given_at'));

        // Invalid option is rejected
        $this->postJson('/api/register', ['first_name' => 'X', 'surname' => 'Y', 'email' => 'x@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123', 'income_band' => 'lots'])
            ->assertStatus(422)->assertJsonValidationErrors('income_band');

        // Existing learner (registered before the consent step) agrees from their profile
        $existing = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $this->actingAs($existing, 'sanctum')->putJson('/api/user/profile', ['consent' => true, 'education_level' => 'degree'])->assertOk();
        $this->assertNotNull($existing->fresh()->consent_given_at);
        $this->app['auth']->forgetGuards();

        // Edit profile (Parts 1–3)
        $token = $res['token'];
        $this->withToken($token)->putJson('/api/user/profile', ['main_work' => 'employed', 'income_band' => '30000_70000', 'surname' => 'Musa'])
            ->assertOk();
        $user->refresh();
        $this->assertSame(['employed', '30000_70000', 'Aisha Musa'], [$user->main_work, $user->income_band, $user->name]);

        // Baseline update: wider disability options, self-description, accommodations,
        // 8 work categories and the split upper income bands.
        $this->withToken($token)->putJson('/api/user/profile', [
            'has_disability' => 'yes', 'disability_types' => ['neurodivergent', 'chronic_health'],
            'disability_description' => 'ADHD; I lose focus in long videos',
            'accommodation_needs' => ['captions', 'flexible_pace'], 'accommodation_notes' => 'Short lessons help',
            'main_work' => 'seasonal_wage', 'income_band' => '300001_500000',
        ])->assertOk()
            ->assertJsonPath('data.user.accommodation_needs', ['captions', 'flexible_pace'])
            ->assertJsonPath('data.user.disability_description', 'ADHD; I lose focus in long videos');
        $user->refresh();
        $this->assertSame(['seasonal_wage', '300001_500000', ['neurodivergent', 'chronic_health']], [$user->main_work, $user->income_band, $user->disability_types]);
        $this->withToken($token)->putJson('/api/user/profile', ['income_band' => 'above_500000', 'main_work' => 'underemployed'])->assertOk();
        // Answers from the earlier form stay valid; unknown ones don't.
        $this->withToken($token)->putJson('/api/user/profile', ['income_band' => 'above_300000', 'main_work' => 'student'])->assertOk();
        $this->withToken($token)->putJson('/api/user/profile', ['accommodation_needs' => ['jetpack']])
            ->assertStatus(422)->assertJsonValidationErrors('accommodation_needs.0');

        // MEL tool (WEO learners): exact categories, adjustments yes/no, value chain track, objective.
        $this->withToken($token)->putJson('/api/user/profile', [
            'has_disability' => 'yes', 'disability_types' => ['psychosocial', 'cognitive_learning', 'seeing'],
            'needs_accommodation' => 'yes', 'accommodation_notes' => 'Flexible pacing',
            'main_work' => 'wage_casual', 'income_band' => '30000_70000',
            'value_chain_track' => 'other', 'value_chain_track_other' => 'Beekeeping', 'training_objective' => 'access_finance',
        ])->assertOk()->assertJsonPath('data.user.training_objective', 'access_finance');
        $this->withToken($token)->putJson('/api/user/profile', ['training_objective' => 'retire'])
            ->assertStatus(422)->assertJsonValidationErrors('training_objective');

        // Enroll with Part 4 answers
        // One-click enroll — no code, no form
        $this->withToken($token)->postJson('/api/enroll', ['course_id' => $course->id])->assertSuccessful();
        $enrollment = Enrollment::where('user_id', $user->id)->firstOrFail();
        $this->withToken($token)->putJson("/api/enrollments/{$enrollment->id}/signup-answers", [
            'learning_type' => 'course', 'learning_mode' => 'blended', 'heard_from' => 'whatsapp', 'partner_hub' => 'Kano Hub',
        ])->assertOk();
        $enrollment->refresh();
        $this->assertSame(['course', 'blended', 'whatsapp', 'Kano Hub'], [$enrollment->learning_type, $enrollment->learning_mode, $enrollment->heard_from, $enrollment->partner_hub]);

        // Nothing pending until the course is completed
        $this->withToken($token)->getJson('/api/course-surveys/pending')->assertOk()->assertJsonCount(0, 'data');
        $enrollment->update(['status' => 'completed', 'completed_at' => now(), 'progress_percentage' => 100]);
        $this->withToken($token)->getJson('/api/course-surveys/pending')
            ->assertJsonPath('data.0.stage', 'completion')->assertJsonPath('data.0.course.title', 'Rice Farming');

        $answers = [
            'enrollment_id' => $enrollment->id, 'stage' => 'completion', 'completed_course' => true,
            'work_change' => 'grew_business', 'started_enterprise' => false, 'feels_respected' => 'yes',
            'income_band' => '70001_150000', 'rating' => 'excellent', 'used_learning' => 'yes',
            'would_recommend' => true, 'improvement' => 'More videos', 'declaration_name' => 'Aisha Musa', 'declaration' => true,
        ];
        $this->withToken($token)->postJson('/api/course-surveys', [...$answers, 'declaration' => false])->assertStatus(422);
        $this->withToken($token)->postJson('/api/course-surveys', $answers)->assertOk();
        $this->withToken($token)->getJson('/api/course-surveys/pending')->assertJsonCount(0, 'data');

        // Six months on, the follow-up is due
        $this->travel(6)->months();
        $this->travel(1)->days();
        $this->withToken($token)->getJson('/api/course-surveys/pending')->assertJsonPath('data.0.stage', 'six_month');
        $this->withToken($token)->postJson('/api/course-surveys', [...$answers, 'stage' => 'six_month', 'work_change' => 'same', 'feels_respected' => 'yes'])->assertOk();
        $this->assertSame(2, $user->courseOutcomeSurveys()->count());
        $this->assertNull($user->courseOutcomeSurveys()->where('stage', 'six_month')->value('feels_respected'), '5.5 only applies when work changed');

        // A learner holding a certificate is asked too, even if the enrollment wasn't marked complete
        $course2 = Course::create(['category_id' => $category->id, 'title' => 'Fish Farming', 'slug' => 'fish-farming', 'description' => 'Intro', 'is_published' => true]);
        $e2 = Enrollment::create(['user_id' => $user->id, 'course_id' => $course2->id, 'status' => 'active', 'enrolled_at' => now(), 'progress_percentage' => 40]);
        \App\Models\Certificate::forceCreate(['user_id' => $user->id, 'course_id' => $course2->id, 'enrollment_id' => $e2->id,
            'certificate_number' => 'CERT-TEST-1', 'issued_date' => now()->toDateString()]);
        $this->withToken($token)->getJson('/api/course-surveys/pending')
            ->assertJsonFragment(['enrollment_id' => $e2->id, 'stage' => 'completion', 'completed_course' => true]);

        // Another learner can't answer for this enrollment
        $other = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $this->actingAs($other, 'sanctum')->postJson('/api/course-surveys', $answers)->assertNotFound();

        // Admin sees it all
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin, 'web');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->get(UserResource::getUrl('edit', ['record' => $user]))->assertOk()->assertSee('Participant profile')->assertSee('Data consent');
        $this->get(EnrollmentResource::getUrl('edit', ['record' => $enrollment]))->assertOk()->assertSee('Part 4');
        $this->get(CourseOutcomeSurveyResource::getUrl('index'))->assertOk()->assertSee('Rice Farming');
        // ...and on the admin dashboard
        $this->get(Filament::getPanel('admin')->getUrl())->assertOk();
        \Livewire\Livewire::test(\App\Filament\Widgets\CourseCheckStatsWidget::class)
            ->assertSee('Would recommend')->assertSee('100%');
        \Livewire\Livewire::test(\App\Filament\Widgets\LatestCourseChecksWidget::class)
            ->assertSee('Latest end-of-course checks')->assertSee('Rice Farming')->assertSee('Excellent');
        \Illuminate\Support\Facades\Cache::forget('baseline-stats');
        \Livewire\Livewire::test(\App\Filament\Widgets\BaselineStatsWidget::class)
            ->assertSee('Learner baseline (WEO)')->assertSee('Living with disability')->assertSee('Earn below minimum wage');
        \Livewire\Livewire::test(\App\Filament\Widgets\BaselineBreakdownWidget::class)
            ->assertSee('Psychosocial &amp; mental health', false)->assertSee('Vision / seeing') // "seeing" (earlier form) counted as vision
            ->assertSee('Wage employee – casual, daily or seasonal labour')->assertSee('Below minimum wage')
            ->assertSee('Other')->assertSee('Access revolving finance / capital support');
        $survey = $user->courseOutcomeSurveys()->first();
        $this->get(CourseOutcomeSurveyResource::getUrl('view', ['record' => $survey]))->assertOk()->assertSee('More videos');
    }
}
