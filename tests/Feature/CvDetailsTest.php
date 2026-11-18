<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\CvService;
use App\Support\CvTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CvDetailsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Notification::fake();
        Queue::fake();
        Storage::fake('local');

        $cat = Category::create(['name' => 'Livestock', 'slug' => 'livestock']);
        $course = Course::create(['category_id' => $cat->id, 'title' => 'Broiler Production', 'slug' => 'broilers', 'description' => 'x', 'is_published' => true]);
        $this->user = User::factory()->cvReady()->create([
            'role' => 'student', 'is_active' => true, 'gender' => 'female', 'date_of_birth' => '2000-05-04', 'state' => 'Kano', 'nationality' => 'nigerian',
        ]);
        Enrollment::create(['user_id' => $this->user->id, 'course_id' => $course->id, 'status' => 'completed', 'progress_percentage' => 100, 'enrolled_at' => now(), 'completed_at' => now()]);
    }

    private function details(array $extra = []): array
    {
        return $extra + [
            'summary' => 'Hard-working poultry attendant ready for a commercial farm.',
            'experiences' => [
                ['title' => 'Farm hand', 'organisation' => 'Old Farm', 'start' => '2019-01', 'end' => '2020-06', 'description' => 'Fed birds'],
                ['title' => 'Poultry attendant', 'organisation' => 'Kano Birds', 'location' => 'Kano', 'start' => '2021-03', 'current' => true, 'description' => 'Brooding and vaccination'],
            ],
            'education' => [['qualification' => 'SSCE', 'institution' => 'GSS Dala', 'end' => '2017-07']],
            'skills' => ['Vaccination', 'Record keeping'],
            'languages' => [['language' => 'Hausa', 'level' => 'Native'], ['language' => 'English', 'level' => 'B2']],
            'achievements' => ['Cut chick mortality to 3%'],
            'references' => [['name' => 'Malam Sani', 'role' => 'Farm manager', 'organisation' => 'Kano Birds', 'phone' => '0803']],
        ];
    }

    public function test_templates_are_listed_and_country_maps_to_a_template(): void
    {
        $keys = array_column($this->actingAs($this->user, 'sanctum')->getJson('/api/cv/templates')->assertOk()->json('data'), 'key');
        $this->assertEqualsCanonicalizing(['nigeria', 'nigeria_public', 'west_africa', 'uk', 'us', 'europass', 'gulf'], $keys);
        $this->assertSame('uk', CvTemplates::forCountry('united kingdom'));
        $this->assertSame('us', CvTemplates::forCountry('Canada'));
        $this->assertSame('nigeria', CvTemplates::forCountry('Mars'));
    }

    public function test_nigerian_private_sector_cv_has_no_personal_details_but_lists_referees(): void
    {
        $data = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/cv/details', ['template' => 'nigeria', 'target_country' => 'Nigeria', 'details' => $this->details()])
            ->assertOk()->json('data.data');

        $this->assertSame('Hard-working poultry attendant ready for a commercial farm.', $data['summary']);
        $this->assertSame('Poultry attendant', $data['experience'][0]['title'], 'current job first');
        $this->assertSame('Vaccination', $data['skills'][0], 'own skills come first');
        $this->assertSame('Broiler Production', $data['courses'][0]['title'], 'training still included');
        $this->assertSame([], $data['personal'], 'Jobberman: no DOB / state of origin / marital status unless asked');
        $this->assertNull($data['photo']);
        $this->assertSame('Malam Sani', $data['references'][0]['name']);
        $this->assertSame('Professional summary', $data['summary_title']);
    }

    public function test_nigerian_government_cv_includes_the_personal_details_forms_ask_for(): void
    {
        $data = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/cv/details', ['template' => 'nigeria_public', 'details' => $this->details(['marital_status' => 'single', 'lga' => 'Dala'])])
            ->assertOk()->json('data.data');

        $this->assertSame(['date_of_birth', 'gender', 'marital_status', 'nationality', 'state_of_origin', 'lga'], array_keys($data['personal']));
        $this->assertSame('Dala', $data['personal']['lga']);
        $this->assertSame('Career objective', $data['summary_title']);
        $this->assertSame('education', $data['section_order'][1]);
    }

    public function test_gulf_cv_shows_visa_status_not_gender(): void
    {
        $data = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/cv/details', ['template' => 'gulf', 'details' => $this->details(['visa_status' => 'Visit visa'])])
            ->assertOk()->json('data.data');
        $this->assertSame('Visit visa', $data['personal']['visa_status']);
        $this->assertArrayNotHasKey('gender', $data['personal']);
    }

    public function test_uk_and_us_templates_drop_personal_details_and_handle_references(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $uk = $this->putJson('/api/cv/details', ['template' => 'uk', 'details' => $this->details()])->assertOk()->json('data.data');
        $this->assertSame([], $uk['personal']);
        $this->assertSame('on_request', $uk['references']);
        $this->assertSame('Personal statement', $uk['summary_title']);

        $us = $this->putJson('/api/cv/details', ['template' => 'us'])->assertOk()->json('data.data');
        $this->assertNull($us['references']);
        $this->assertSame('experience', $us['section_order'][1]);
        $this->assertSame('Farm hand', end($us['experience'])['title'], 'details kept when only the template changes');
    }

    public function test_pdf_renders_for_every_template(): void
    {
        $this->actingAs($this->user, 'sanctum')->putJson('/api/cv/details', ['details' => $this->details()])->assertOk();
        foreach (CvTemplates::keys() as $key) {
            $this->putJson('/api/cv/details', ['template' => $key])->assertOk();
            $res = $this->get('/api/cv/pdf')->assertOk();
            $this->assertStringStartsWith('%PDF', $res->getContent(), $key);
        }
    }

    public function test_employer_view_hides_personal_details_and_referees_until_accepted(): void
    {
        $this->actingAs($this->user, 'sanctum')->putJson('/api/cv/details', ['template' => 'gulf', 'details' => $this->details()])->assertOk();
        $data = app(CvService::class)->forUser($this->user->fresh())->data;

        $hidden = CvService::forEmployer($data);
        $this->assertArrayNotHasKey('personal', $hidden);
        $this->assertArrayNotHasKey('contact', $hidden);
        $this->assertSame('on_request', $hidden['references']);

        $shown = CvService::forEmployer($data, true);
        $this->assertArrayHasKey('nationality', $shown['personal']);
        $this->assertSame('Malam Sani', $shown['references'][0]['name']);
    }

    public function test_validation(): void
    {
        $this->actingAs($this->user, 'sanctum')->putJson('/api/cv/details', [
            'template' => 'mars',
            'details' => ['experiences' => [['organisation' => 'No title', 'start' => '2020/01']]],
        ])->assertStatus(422)->assertJsonValidationErrors(['template', 'details.experiences.0.title', 'details.experiences.0.start']);
    }

    public function test_claude_suggests_cv_wording_from_the_draft(): void
    {
        $fake = new class extends \App\Services\ClaudeTextService {
            public array $calls = [];
            public function complete(string $system, string $prompt, int $maxTokens = 4096): ?string
            {
                $this->calls[] = $prompt;
                return str_contains($prompt, 'skills') && str_contains($prompt, 'One per line, 1 to 4')
                    ? "- Brooding\n- Vaccination\n2. Feed formulation"
                    : "\"• Managed [number] birds\"";
            }
        };
        $this->app->instance(\App\Services\ClaudeTextService::class, $fake);
        $this->actingAs($this->user, 'sanctum');

        $res = $this->postJson('/api/cv/suggest', ['field' => 'experience', 'index' => 1, 'template' => 'uk', 'details' => $this->details()])->assertOk();
        $this->assertSame('• Managed [number] birds', $res->json('data.suggestion'));
        $this->assertStringContainsString('Poultry attendant at Kano Birds', $fake->calls[0]);
        $this->assertStringContainsString('Completed Agrisiti course: Broiler Production', $fake->calls[0]);

        $skills = $this->postJson('/api/cv/suggest', ['field' => 'skills', 'details' => $this->details()])->assertOk()->json('data.suggestion');
        $this->assertSame(['Brooding', 'Feed formulation'], $skills, 'bullets stripped, already-listed skills dropped');

        $this->postJson('/api/cv/suggest', ['field' => 'poem'])->assertStatus(422);
    }

    public function test_suggest_returns_503_when_claude_is_unavailable(): void
    {
        config(['claude.api_key' => null, 'services.bedrock.key' => null]);
        $this->actingAs($this->user, 'sanctum')->postJson('/api/cv/suggest', ['field' => 'summary'])->assertStatus(503);
    }

    public function test_cv_is_locked_until_the_profile_form_is_filled(): void
    {
        $this->user->update(['income_band' => null, 'value_chain_track' => null]);
        $this->actingAs($this->user, 'sanctum');

        $res = $this->getJson('/api/cv')->assertStatus(403)->assertJsonPath('code', 'profile_incomplete');
        $this->assertSame(['income_band', 'value_chain_track'], $res->json('errors.missing'));
        $this->get('/api/cv/pdf')->assertStatus(403);
        $this->putJson('/api/cv/details', ['template' => 'uk'])->assertStatus(403);
        $this->postJson('/api/cv/suggest', ['field' => 'summary'])->assertStatus(403);

        $this->user->update(['income_band' => 'none', 'value_chain_track' => 'poultry']);
        $this->getJson('/api/cv')->assertOk();
    }

    public function test_disability_and_adjustment_follow_ups_are_required_when_yes(): void
    {
        $this->actingAs($this->user, 'sanctum');
        $this->putJson('/api/user/profile', ['has_disability' => 'yes', 'disability_types' => []])
            ->assertStatus(422)->assertJsonValidationErrors(['disability_types']);
        $this->putJson('/api/user/profile', ['needs_accommodation' => 'yes'])
            ->assertStatus(422)->assertJsonValidationErrors(['accommodation_notes']);
        $this->putJson('/api/user/profile', ['has_disability' => 'yes', 'disability_types' => ['neurodivergent'], 'needs_accommodation' => 'yes', 'accommodation_notes' => 'Flexible pacing'])
            ->assertOk();
        $this->assertSame(['neurodivergent'], $this->user->fresh()->disability_types);
    }

    public function test_participant_options_api_and_follow_ups_cleared_when_not_yes(): void
    {
        $q = collect($this->getJson('/api/participant/options')->assertOk()->json('data.questions'))->keyBy('field');
        $this->assertSame('multi', $q['disability_types']['type']);
        $this->assertSame(['has_disability' => 'yes'], $q['disability_types']['show_if']);
        $this->assertContains('neurodivergent', array_column($q['disability_types']['options'], 'value'));
        $this->assertContains('self_describe', array_column($q['disability_types']['options'], 'value'));
        $this->assertSame(['disability_types' => 'self_describe'], $q['disability_description']['show_if']);
        $this->assertSame(['needs_accommodation' => 'yes'], $q['accommodation_notes']['required_if']);
        $this->assertSame(['yes', 'no', 'prefer_not_to_say'], array_column($q['has_disability']['options'], 'value'));

        $this->actingAs($this->user, 'sanctum');
        $this->putJson('/api/user/profile', ['has_disability' => 'yes', 'disability_types' => ['vision', 'prefer_not_to_say']])->assertOk();
        $this->assertSame(['prefer_not_to_say'], $this->user->fresh()->disability_types, 'prefer not to say stands alone');

        $this->putJson('/api/user/profile', ['has_disability' => 'yes', 'disability_types' => ['vision', 'self_describe']])
            ->assertStatus(422)->assertJsonValidationErrors(['disability_description']);
        $this->putJson('/api/user/profile', ['has_disability' => 'yes', 'disability_types' => ['vision'], 'disability_description' => 'Dropped: no self-describe tick'])->assertOk();
        $this->assertNull($this->user->fresh()->disability_description);
        $this->putJson('/api/user/profile', ['has_disability' => 'yes', 'disability_types' => ['vision', 'self_describe'], 'disability_description' => 'Low vision in one eye'])->assertOk();
        $this->assertSame('Low vision in one eye', $this->user->fresh()->disability_description);
        $this->putJson('/api/user/profile', ['has_disability' => 'no', 'needs_accommodation' => 'no'])->assertOk();
        $u = $this->user->fresh();
        $this->assertSame([], $u->disability_types);
        $this->assertNull($u->disability_description);
    }
}
