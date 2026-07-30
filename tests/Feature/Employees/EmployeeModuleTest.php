<?php

namespace Tests\Feature\Employees;

use App\Models\AuthActivityLog;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\AuthSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthSeeder::class);
    }

    public function test_admin_can_list_employees(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $this->actingAs($admin)
            ->getJson('/api/v1/employees')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure(['data' => ['items', 'meta', 'can_manage']]);
    }

    public function test_create_provisions_user_with_employee_role(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/employees', [
                'employee_number' => 'EMP-9001',
                'first_name' => 'New',
                'last_name' => 'Hire',
                'email' => 'newhire@pcspc.local',
                'employment_status' => 'active',
                'position_title' => 'Analyst',
                'tin' => '111-222-333-444',
            ])
            ->assertCreated()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.employee.email', 'newhire@pcspc.local')
            ->assertJsonStructure(['data' => ['employee', 'temporary_password']]);

        $temporaryPassword = $response->json('data.temporary_password');
        $this->assertNotEmpty($temporaryPassword);

        $user = User::query()->where('email', 'newhire@pcspc.local')->firstOrFail();
        $this->assertTrue($user->roles()->where('slug', 'employee')->exists());
        $this->assertSame('EMP-9001', $user->employee_number);
        $this->assertTrue(Employee::query()->where('employee_number', 'EMP-9001')->where('user_id', $user->id)->exists());
        $this->assertTrue(AuthActivityLog::query()->where('event', 'employee.created')->exists());
    }

    public function test_statutory_fields_are_masked_in_index(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/employees')
            ->assertOk();

        $adminRow = collect($response->json('data.items'))
            ->firstWhere('employee_number', 'EMP-0001');

        $this->assertNotNull($adminRow);
        $this->assertNotSame('123-456-789-000', $adminRow['tin']);
        $this->assertStringEndsWith('-000', $adminRow['tin'] ?? '');
        $this->assertStringContainsString('*', $adminRow['tin'] ?? '');
    }

    public function test_employee_cannot_manage_employees(): void
    {
        $employee = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();

        $this->actingAs($employee)
            ->postJson('/api/v1/employees', [
                'employee_number' => 'EMP-9999',
                'first_name' => 'Nope',
                'last_name' => 'Allowed',
                'email' => 'nope@pcspc.local',
                'employment_status' => 'active',
            ])
            ->assertStatus(403);
    }

    public function test_deactivate_disables_linked_user(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $target = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();
        $linkedUser = User::query()->findOrFail($target->user_id);
        $this->assertTrue($linkedUser->is_active);

        $this->actingAs($admin)
            ->postJson("/api/v1/employees/{$target->uuid}/deactivate")
            ->assertOk()
            ->assertJsonPath('data.employee.employment_status', 'inactive');

        $this->assertFalse($linkedUser->fresh()->is_active);
    }
}
