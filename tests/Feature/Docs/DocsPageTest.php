<?php

namespace Tests\Feature\Docs;

use App\Models\User;
use Database\Seeders\AuthSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthSeeder::class);
    }

    public function test_guest_cannot_view_docs(): void
    {
        $this->get('/docs/flowcharts')->assertRedirect();
    }

    public function test_authenticated_user_can_view_flowcharts_doc(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $response = $this->actingAs($admin)
            ->get('/docs/flowcharts')
            ->assertOk()
            ->assertSee('Flowcharts', false)
            ->assertSee('FLOWCHARTS.md', false)
            ->assertSee('docs-source', false);

        $source = $response->getContent() ?: '';
        $this->assertStringContainsString('hris-flowcharts.canvas.tsx', $source);
        $this->assertMatchesRegularExpression('/flowchart\\s+TD/', $source);
        $this->assertStringContainsString('Leave \\/ OT approval', $source);
    }

    public function test_help_page_links_to_flowcharts(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $this->actingAs($admin)
            ->get('/modules/help')
            ->assertOk()
            ->assertSee(route('docs.show', ['doc' => 'flowcharts']), false)
            ->assertSee('Flowcharts', false);
    }
}
