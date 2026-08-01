<?php

namespace Tests\Feature\Employees;

use App\Models\AuthActivityLog;
use App\Models\Employee;
use App\Models\EmployeeCareerHistory;
use App\Models\User;
use Database\Seeders\AuthSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeCareerHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthSeeder::class);
    }

    public function test_admin_can_manage_career_history(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();

        $create = $this->actingAs($admin)
            ->postJson("/api/v1/employees/{$employee->uuid}/career-history", [
                'position_title' => 'HR Specialist',
                'employment_category' => 'probationary',
                'basic_salary' => 25000.50,
                'salary_rate_type' => 'monthly',
                'currency' => 'PHP',
                'effective_from' => '2023-01-15',
                'effective_to' => '2023-07-14',
                'is_current' => false,
            ])
            ->assertCreated()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.history.position_title', 'HR Specialist')
            ->assertJsonPath('data.history.employment_category', 'probationary')
            ->assertJsonPath('data.history.basic_salary', '25000.50')
            ->assertJsonPath('data.history.is_current', false);

        $historyId = $create->json('data.history.id');
        $this->assertNotEmpty($historyId);
        $this->assertTrue(AuthActivityLog::query()->where('event', 'employee.career_history.created')->exists());

        $stored = EmployeeCareerHistory::query()->where('uuid', $historyId)->firstOrFail();
        $this->assertNotSame('25000.50', $stored->getAttributes()['basic_salary'] ?? null);

        $this->actingAs($admin)
            ->getJson("/api/v1/employees/{$employee->uuid}/career-history")
            ->assertOk()
            ->assertJsonPath('data.items.0.id', $historyId)
            ->assertJsonStructure(['data' => ['categories', 'category_options', 'rate_types']]);

        $this->actingAs($admin)
            ->putJson("/api/v1/employees/{$employee->uuid}/career-history/{$historyId}", [
                'position_title' => 'Senior HR Specialist',
                'employment_category' => 'permanent',
                'basic_salary' => 32000,
                'salary_rate_type' => 'monthly',
                'currency' => 'PHP',
                'effective_from' => '2023-07-15',
                'effective_to' => null,
                'is_current' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.history.position_title', 'Senior HR Specialist')
            ->assertJsonPath('data.history.employment_category', 'permanent')
            ->assertJsonPath('data.history.is_current', true)
            ->assertJsonPath('data.history.effective_to', null);

        $this->assertTrue(AuthActivityLog::query()->where('event', 'employee.career_history.updated')->exists());
        $this->assertSame('Senior HR Specialist', $employee->fresh()->position_title);

        $this->actingAs($admin)
            ->deleteJson("/api/v1/employees/{$employee->uuid}/career-history/{$historyId}")
            ->assertOk();

        $this->assertFalse(EmployeeCareerHistory::query()->where('uuid', $historyId)->exists());
        $this->assertTrue(AuthActivityLog::query()->where('event', 'employee.career_history.deleted')->exists());
    }

    public function test_marking_current_clears_previous_current(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();

        $first = $this->actingAs($admin)
            ->postJson("/api/v1/employees/{$employee->uuid}/career-history", [
                'position_title' => 'Staff',
                'employment_category' => 'probationary',
                'salary_rate_type' => 'monthly',
                'effective_from' => '2020-01-01',
                'is_current' => true,
            ])
            ->assertCreated()
            ->json('data.history.id');

        $second = $this->actingAs($admin)
            ->postJson("/api/v1/employees/{$employee->uuid}/career-history", [
                'position_title' => 'Lead',
                'employment_category' => 'permanent',
                'salary_rate_type' => 'monthly',
                'effective_from' => '2022-01-01',
                'is_current' => true,
            ])
            ->assertCreated()
            ->json('data.history.id');

        $this->assertFalse(EmployeeCareerHistory::query()->where('uuid', $first)->value('is_current'));
        $this->assertTrue(EmployeeCareerHistory::query()->where('uuid', $second)->value('is_current'));
        $this->assertSame('Lead', $employee->fresh()->position_title);
    }

    public function test_career_validation_requires_fields_and_end_or_current(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();

        $this->actingAs($admin)
            ->postJson("/api/v1/employees/{$employee->uuid}/career-history", [])
            ->assertStatus(422)
            ->assertJsonPath('errors.position_title.0', 'Please enter the position title.')
            ->assertJsonPath('errors.employment_category.0', 'Please choose an employment category.')
            ->assertJsonPath('errors.effective_from.0', 'Please enter the effective date.');

        $this->actingAs($admin)
            ->postJson("/api/v1/employees/{$employee->uuid}/career-history", [
                'position_title' => 'Clerk',
                'employment_category' => 'casual',
                'salary_rate_type' => 'daily',
                'effective_from' => '2021-01-01',
                'is_current' => false,
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.effective_to.0', 'Please enter an end date, or mark this as the current record.');
    }

    public function test_employee_cannot_manage_career_history(): void
    {
        $employeeUser = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();

        $this->actingAs($employeeUser)
            ->getJson("/api/v1/employees/{$employee->uuid}/career-history")
            ->assertStatus(403);

        $this->actingAs($employeeUser)
            ->postJson("/api/v1/employees/{$employee->uuid}/career-history", [
                'position_title' => 'Nope',
                'employment_category' => 'permanent',
                'salary_rate_type' => 'monthly',
                'effective_from' => '2020-01-01',
                'is_current' => true,
            ])
            ->assertStatus(403);
    }

    public function test_career_belongs_to_employee_scope(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employeeA = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();
        $employeeB = Employee::query()->where('employee_number', 'EMP-0001')->firstOrFail();

        $history = EmployeeCareerHistory::query()->create([
            'employee_id' => $employeeA->id,
            'position_title' => 'Scoped',
            'employment_category' => 'permanent',
            'salary_rate_type' => 'monthly',
            'currency' => 'PHP',
            'effective_from' => '2019-01-01',
            'effective_to' => '2020-01-01',
            'is_current' => false,
        ]);

        $this->actingAs($admin)
            ->putJson("/api/v1/employees/{$employeeB->uuid}/career-history/{$history->uuid}", [
                'position_title' => 'Scoped',
                'employment_category' => 'permanent',
                'salary_rate_type' => 'monthly',
                'effective_from' => '2019-01-01',
                'effective_to' => '2020-01-01',
                'is_current' => false,
            ])
            ->assertNotFound();
    }

    public function test_audit_meta_does_not_store_salary_amount(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();

        $this->actingAs($admin)
            ->postJson("/api/v1/employees/{$employee->uuid}/career-history", [
                'position_title' => 'Analyst',
                'employment_category' => 'contractual',
                'basic_salary' => 45000,
                'salary_rate_type' => 'monthly',
                'effective_from' => '2024-01-01',
                'is_current' => true,
            ])
            ->assertCreated();

        $meta = AuthActivityLog::query()
            ->where('event', 'employee.career_history.created')
            ->latest('id')
            ->value('meta');

        $this->assertIsArray($meta);
        $this->assertTrue($meta['has_salary'] ?? false);
        $this->assertArrayNotHasKey('basic_salary', $meta);
    }
}
