<?php

namespace Tests\Feature;

use App\Filament\Resources\OrganisationResource;
use App\Filament\Resources\OrganisationResource\Pages\CreateOrganisation;
use App\Filament\Resources\OrganisationResource\Pages\ListOrganisations;
use App\Models\Organisation;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAddsOrganisationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.env' => 'local']);
        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_list_has_an_add_button_and_admin_creates_an_organisation_with_its_login(): void
    {
        $this->get(OrganisationResource::getUrl('index'))->assertOk()->assertSee('Add organisation');
        Livewire::test(ListOrganisations::class)->assertActionExists(CreateAction::class);

        $this->get(OrganisationResource::getUrl('create'))->assertOk();
        Livewire::test(CreateOrganisation::class)
            ->fillForm([
                'contact_name' => 'Ada Farms',
                'contact_email' => 'hr@adafarms.test',
                'contact_password' => 'secret-pass-1',
                'name' => 'Ada Farms Ltd',
                'organisation_type' => 'farm',
                'country' => 'Nigeria',
                'state' => 'Oyo',
                'is_approved' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $org = Organisation::where('name', 'Ada Farms Ltd')->firstOrFail();
        $this->assertTrue($org->is_approved);
        $this->assertSame($this->admin->id, $org->approved_by);
        $this->assertNotNull($org->approved_at);
        $this->assertSame('organisation', $org->user->role);
        $this->assertSame('hr@adafarms.test', $org->user->email);
        $this->assertTrue(Hash::check('secret-pass-1', $org->user->password));
    }

    public function test_non_admins_cannot_add_organisations(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'tutor', 'is_active' => true]));
        $this->assertFalse(OrganisationResource::canCreate());
    }
}
