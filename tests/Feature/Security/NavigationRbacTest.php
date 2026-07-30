<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Support\Navigation;
use Database\Seeders\AuthSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthSeeder::class);
    }

    public function test_employee_sidebar_hides_admin_modules(): void
    {
        $employee = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();
        $employee->load('roles.permissions');

        $keys = collect(Navigation::sections($employee))
            ->flatMap(fn (array $section) => collect($section['items'])->pluck('key'))
            ->values()
            ->all();

        $this->assertContains('dashboard', $keys);
        $this->assertContains('leave', $keys);
        $this->assertContains('timekeeping', $keys);
        $this->assertNotContains('administration', $keys);
        $this->assertNotContains('security', $keys);
        $this->assertNotContains('audit', $keys);
        $this->assertNotContains('departments', $keys);
        $this->assertNotContains('employees', $keys);
    }

    public function test_employee_cannot_open_admin_module_page(): void
    {
        $employee = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();

        $this->actingAs($employee)
            ->get('/modules/security')
            ->assertForbidden();

        $this->actingAs($employee)
            ->get('/modules/administration')
            ->assertForbidden();
    }

    public function test_admin_can_open_security_module_page(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $this->actingAs($admin)
            ->get('/modules/security')
            ->assertOk();
    }
}
