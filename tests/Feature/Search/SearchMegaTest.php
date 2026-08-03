<?php

namespace Tests\Feature\Search;

use App\Models\User;
use Database\Seeders\AuthSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchMegaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthSeeder::class);
    }

    public function test_authenticated_user_can_browse_search_mega(): void
    {
        $employee = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();

        $this->actingAs($employee)
            ->getJson('/api/v1/search')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'data' => [
                    'query',
                    'modules',
                    'people',
                    'shortcuts',
                    'sections',
                ],
            ]);
    }

    public function test_search_filters_modules_by_query(): void
    {
        $employee = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();

        $response = $this->actingAs($employee)
            ->getJson('/api/v1/search?q=leave')
            ->assertOk();

        $modules = collect($response->json('data.modules'));
        $this->assertTrue(
            $modules->contains(fn (array $item): bool => str_contains(strtolower($item['label']), 'leave')),
        );
        $this->assertFalse(
            $modules->contains(fn (array $item): bool => str_contains(strtolower($item['label']), 'audit')),
        );
    }

    public function test_admin_search_includes_people(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/search?q=demo')
            ->assertOk();

        $people = collect($response->json('data.people'));
        $this->assertNotEmpty($people);
        $this->assertTrue(
            $people->contains(fn (array $item): bool => str_contains(strtolower($item['full_name'] ?? ''), 'demo')),
        );
    }

    public function test_employee_search_excludes_people_without_permission(): void
    {
        $employee = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();

        $this->actingAs($employee)
            ->getJson('/api/v1/search?q=admin')
            ->assertOk()
            ->assertJsonPath('data.people', []);
    }

    public function test_guest_cannot_search(): void
    {
        $this->getJson('/api/v1/search')->assertUnauthorized();
    }
}
