<?php

namespace Tests\Feature\Lookups;

use App\Models\AuthActivityLog;
use App\Models\LookupValue;
use App\Models\User;
use Database\Seeders\AuthSeeder;
use Database\Seeders\Lookups\LookupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LookupModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthSeeder::class);
        $this->seed(LookupSeeder::class);
    }

    public function test_admin_can_list_manage_and_read_lookup_options(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $this->actingAs($admin)
            ->getJson('/api/v1/lookups?type=gender')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonFragment(['code' => 'male']);

        $create = $this->actingAs($admin)
            ->postJson('/api/v1/lookups', [
                'type' => 'gender',
                'code' => 'prefer_not_to_say',
                'label' => 'Prefer not to say',
                'sort_order' => 40,
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.lookup.code', 'prefer_not_to_say');

        $id = $create->json('data.lookup.id');
        $this->assertTrue(AuthActivityLog::query()->where('event', 'lookup.created')->exists());

        $this->actingAs($admin)
            ->getJson('/api/v1/lookups/options?types=gender,employment_status')
            ->assertOk()
            ->assertJsonFragment(['code' => 'prefer_not_to_say'])
            ->assertJsonFragment(['code' => 'active']);

        $this->actingAs($admin)
            ->putJson("/api/v1/lookups/{$id}", [
                'label' => 'Prefer not to disclose',
                'sort_order' => 50,
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.lookup.label', 'Prefer not to disclose');

        $this->actingAs($admin)
            ->deleteJson("/api/v1/lookups/{$id}")
            ->assertOk();

        $this->assertDatabaseMissing('lookup_values', ['uuid' => $id]);
    }

    public function test_system_lookup_cannot_be_deleted(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $system = LookupValue::query()->where('type', 'employment_status')->where('code', 'active')->firstOrFail();

        $this->actingAs($admin)
            ->deleteJson('/api/v1/lookups/'.$system->uuid)
            ->assertStatus(422);
    }

    public function test_employee_cannot_manage_lookups_but_can_read_options(): void
    {
        $employee = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();

        $this->actingAs($employee)
            ->getJson('/api/v1/lookups')
            ->assertForbidden();

        $this->actingAs($employee)
            ->getJson('/api/v1/lookups/options?types=gender')
            ->assertOk()
            ->assertJsonPath('status', true);
    }

    public function test_admin_can_open_lookups_module_page(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $this->actingAs($admin)
            ->get('/modules/lookups')
            ->assertOk()
            ->assertSee('ADM-006', false)
            ->assertSee('Lookup values', false);
    }

    public function test_employee_meta_includes_lookup_options(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $this->actingAs($admin)
            ->getJson('/api/v1/employees/meta')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'statuses',
                    'status_options',
                    'genders',
                    'civil_statuses',
                    'dependent_relationship_options',
                    'education_level_options',
                ],
            ])
            ->assertJsonFragment(['code' => 'male']);
    }
}
