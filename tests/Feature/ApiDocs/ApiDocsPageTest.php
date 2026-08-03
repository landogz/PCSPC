<?php

namespace Tests\Feature\ApiDocs;

use App\Models\User;
use App\Support\Navigation;
use Database\Seeders\AuthSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiDocsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthSeeder::class);
    }

    public function test_guest_can_view_public_api_docs_page(): void
    {
        $this->get('/api-docs')
            ->assertOk()
            ->assertSee('PCSPC API Reference', false)
            ->assertSee('/api/v1/auth/login', false)
            ->assertSee('/api/v1/schedules/print', false)
            ->assertSee('/api/v1/employees', false)
            ->assertSee('Quick start', false)
            ->assertSee('Code examples', false)
            ->assertSee('cURL', false)
            ->assertSee('Python', false)
            ->assertSee('Java', false)
            ->assertSee('data-module="api-docs"', false)
            ->assertSee('data-api-docs-code-panel', false)
            ->assertSee('data-api-docs-group-nav', false)
            ->assertSee('Modules', false);
    }

    public function test_guest_can_fetch_api_docs_json_catalog_with_per_endpoint_examples(): void
    {
        $response = $this->getJson('/api-docs.json')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'data' => [
                    'title',
                    'totals' => ['endpoints', 'groups'],
                    'groups',
                    'conventions',
                    'example_languages',
                    'featured_examples',
                ],
            ]);

        $this->assertGreaterThan(20, $response->json('data.totals.endpoints'));
        $groupKeys = collect($response->json('data.groups'))->pluck('key')->all();
        $this->assertContains('auth', $groupKeys);
        $this->assertContains('employees', $groupKeys);
        $this->assertContains('schedules', $groupKeys);
        $this->assertContains('notifications', $groupKeys);
        $this->assertContains('search', $groupKeys);
        $this->assertNotEmpty($response->json('data.featured_examples'));

        $firstEndpoint = collect($response->json('data.groups'))
            ->flatMap(fn (array $group) => $group['endpoints'] ?? [])
            ->first();

        $this->assertIsArray($firstEndpoint);
        $this->assertArrayHasKey('examples', $firstEndpoint);
        $this->assertArrayHasKey('curl', $firstEndpoint['examples']);
        $this->assertArrayHasKey('php', $firstEndpoint['examples']);
        $this->assertArrayHasKey('python', $firstEndpoint['examples']);
        $this->assertArrayHasKey('java', $firstEndpoint['examples']);
        $this->assertArrayHasKey('javascript', $firstEndpoint['examples']);
        $this->assertArrayHasKey('csharp', $firstEndpoint['examples']);

        $endpointsWithExamples = collect($response->json('data.groups'))
            ->flatMap(fn (array $group) => $group['endpoints'] ?? [])
            ->filter(fn (array $endpoint) => ! empty($endpoint['examples']['curl']))
            ->count();

        $this->assertSame(
            (int) $response->json('data.totals.endpoints'),
            $endpointsWithExamples
        );
    }

    public function test_authenticated_user_sees_api_docs_in_sidebar_layout(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $this->actingAs($admin)
            ->get('/api-docs')
            ->assertOk()
            ->assertSee('API Docs', false)
            ->assertSee('Quick start', false)
            ->assertSee('data-module="api-docs"', false);

        $item = Navigation::find('api-docs');
        $this->assertNotNull($item);
        $this->assertSame('api-docs', $item['route']);
        $this->assertSame(route('api-docs'), Navigation::href($item));
        $this->assertNotContains('api-docs', Navigation::moduleKeys());
    }
}
