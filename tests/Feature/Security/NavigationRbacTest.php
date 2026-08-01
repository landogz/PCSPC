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
        $this->assertNotContains('holidays', $keys);
        $this->assertNotContains('shifts', $keys);
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

    public function test_admin_can_open_training_and_medical_stub_pages(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $this->actingAs($admin)
            ->get('/modules/training')
            ->assertOk()
            ->assertSee('EMP-005', false)
            ->assertSee('Planned for Phase 6', false);

        $this->actingAs($admin)
            ->get('/modules/medical')
            ->assertOk()
            ->assertSee('EMP-006', false)
            ->assertSee('Planned for Phase 6', false);
    }

    public function test_employee_cannot_open_training_or_medical_module_pages(): void
    {
        $employee = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();

        $this->actingAs($employee)
            ->get('/modules/training')
            ->assertForbidden();

        $this->actingAs($employee)
            ->get('/modules/medical')
            ->assertForbidden();
    }

    public function test_employees_module_includes_training_and_medical_stub_tabs(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $this->actingAs($admin)
            ->get('/modules/employees')
            ->assertOk()
            ->assertSee('data-tab="training"', false)
            ->assertSee('data-tab="medical"', false)
            ->assertSee('EMP-005', false)
            ->assertSee('EMP-006', false);
    }
}
