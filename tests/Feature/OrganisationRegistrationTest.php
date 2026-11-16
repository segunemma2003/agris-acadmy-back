<?php

namespace Tests\Feature;

use App\Models\Organisation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrganisationRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Notification::fake();
        Queue::fake();
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'contact_name' => 'Ngozi Obi', 'email' => 'hr@greenfarms.ng', 'phone' => '+2348030000000',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'name' => 'Green Farms Ltd', 'organisation_type' => 'farm', 'country' => 'Nigeria', 'state' => 'Kano',
            'talent_needs' => '5 poultry attendants',
        ];
    }

    public function test_nigerian_organisation_registers_pending_approval_and_cannot_post_yet(): void
    {
        $res = $this->postJson('/api/organisations/register', $this->payload())->assertCreated();
        $org = Organisation::firstOrFail();

        $this->assertFalse($org->is_approved);
        $this->assertSame('farm', $org->organisation_type);
        $this->assertSame('5 poultry attendants', $org->talent_needs);
        $this->assertSame('organisation', $org->user->role);

        // Token works, but posting an opening waits for approval
        $token = $res->json('data.token');
        $this->withToken($token)->postJson('/api/org/slots', [
            'title' => 'Poultry attendant', 'description' => 'x', 'openings' => 2,
        ])->assertForbidden();
    }

    public function test_international_organisation_gives_city_instead_of_state(): void
    {
        $this->postJson('/api/organisations/register', $this->payload([
            'email' => 'talent@agrijobs.co.uk', 'country' => 'United Kingdom', 'state' => null,
            'organisation_type' => 'recruitment_agency',
        ]))->assertStatus(422)->assertJsonValidationErrors('city');

        $this->postJson('/api/organisations/register', $this->payload([
            'email' => 'talent@agrijobs.co.uk', 'country' => 'United Kingdom', 'state' => null, 'city' => 'Leeds',
            'organisation_type' => 'recruitment_agency',
        ]))->assertCreated();
        $this->assertSame('Leeds', Organisation::where('country', 'United Kingdom')->value('city'));
    }

    public function test_required_fields(): void
    {
        $this->postJson('/api/organisations/register', $this->payload([
            'organisation_type' => null, 'talent_needs' => null, 'state' => null,
        ]))->assertStatus(422)->assertJsonValidationErrors(['organisation_type', 'talent_needs', 'state']);
    }
}
