<?php

namespace Tests\Feature\Employees;

use App\Models\AuthActivityLog;
use App\Models\Employee;
use App\Models\EmployeeEmploymentHistory;
use App\Models\User;
use Database\Seeders\AuthSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeEmploymentHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthSeeder::class);
    }

    public function test_admin_can_manage_employment_history(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();

        $create = $this->actingAs($admin)
            ->postJson("/api/v1/employees/{$employee->uuid}/employment-history", [
                'employer_name' => 'Acme Corp',
                'position_title' => 'Analyst',
                'location' => 'Manila',
                'date_from' => '2018-01-15',
                'date_to' => '2020-06-30',
                'is_current' => false,
            ])
            ->assertCreated()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.history.employer_name', 'Acme Corp')
            ->assertJsonPath('data.history.is_current', false);

        $historyId = $create->json('data.history.id');
        $this->assertNotEmpty($historyId);
        $this->assertTrue(AuthActivityLog::query()->where('event', 'employee.employment_history.created')->exists());

        $this->actingAs($admin)
            ->getJson("/api/v1/employees/{$employee->uuid}/employment-history")
            ->assertOk()
            ->assertJsonPath('data.items.0.id', $historyId);

        $this->actingAs($admin)
            ->putJson("/api/v1/employees/{$employee->uuid}/employment-history/{$historyId}", [
                'employer_name' => 'Acme Corp',
                'position_title' => 'Senior Analyst',
                'location' => 'Makati',
                'date_from' => '2018-01-15',
                'date_to' => null,
                'is_current' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.history.position_title', 'Senior Analyst')
            ->assertJsonPath('data.history.is_current', true)
            ->assertJsonPath('data.history.date_to', null);

        $this->assertTrue(AuthActivityLog::query()->where('event', 'employee.employment_history.updated')->exists());

        $this->actingAs($admin)
            ->deleteJson("/api/v1/employees/{$employee->uuid}/employment-history/{$historyId}")
            ->assertOk();

        $this->assertFalse(EmployeeEmploymentHistory::query()->where('uuid', $historyId)->exists());
        $this->assertTrue(AuthActivityLog::query()->where('event', 'employee.employment_history.deleted')->exists());
    }

    public function test_marking_current_clears_previous_current(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();

        $first = $this->actingAs($admin)
            ->postJson("/api/v1/employees/{$employee->uuid}/employment-history", [
                'employer_name' => 'First Co',
                'position_title' => 'Staff',
                'date_from' => '2015-01-01',
                'is_current' => true,
            ])
            ->assertCreated()
            ->json('data.history.id');

        $second = $this->actingAs($admin)
            ->postJson("/api/v1/employees/{$employee->uuid}/employment-history", [
                'employer_name' => 'Second Co',
                'position_title' => 'Lead',
                'date_from' => '2020-01-01',
                'is_current' => true,
            ])
            ->assertCreated()
            ->json('data.history.id');

        $this->assertFalse(EmployeeEmploymentHistory::query()->where('uuid', $first)->value('is_current'));
        $this->assertTrue(EmployeeEmploymentHistory::query()->where('uuid', $second)->value('is_current'));
    }

    public function test_history_validation_requires_employer_and_end_or_current(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();

        $this->actingAs($admin)
            ->postJson("/api/v1/employees/{$employee->uuid}/employment-history", [])
            ->assertStatus(422)
            ->assertJsonPath('errors.employer_name.0', 'Please enter the employer name.')
            ->assertJsonPath('errors.position_title.0', 'Please enter the position title.')
            ->assertJsonPath('errors.date_from.0', 'Please enter the start date.');

        $this->actingAs($admin)
            ->postJson("/api/v1/employees/{$employee->uuid}/employment-history", [
                'employer_name' => 'Past Co',
                'position_title' => 'Clerk',
                'date_from' => '2019-01-01',
                'is_current' => false,
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.date_to.0', 'Please enter an end date, or mark this as the current job.');
    }

    public function test_employee_cannot_manage_employment_history(): void
    {
        $employeeUser = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();

        $this->actingAs($employeeUser)
            ->getJson("/api/v1/employees/{$employee->uuid}/employment-history")
            ->assertStatus(403);

        $this->actingAs($employeeUser)
            ->postJson("/api/v1/employees/{$employee->uuid}/employment-history", [
                'employer_name' => 'Nope',
                'position_title' => 'Allowed',
                'date_from' => '2020-01-01',
                'is_current' => true,
            ])
            ->assertStatus(403);
    }

    public function test_history_belongs_to_employee_scope(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employeeA = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();
        $employeeB = Employee::query()->where('employee_number', 'EMP-0001')->firstOrFail();

        $history = EmployeeEmploymentHistory::query()->create([
            'employee_id' => $employeeA->id,
            'employer_name' => 'Scoped Co',
            'position_title' => 'Staff',
            'date_from' => '2019-01-01',
            'date_to' => '2020-01-01',
            'is_current' => false,
        ]);

        $this->actingAs($admin)
            ->putJson("/api/v1/employees/{$employeeB->uuid}/employment-history/{$history->uuid}", [
                'employer_name' => 'Scoped Co',
                'position_title' => 'Staff',
                'date_from' => '2019-01-01',
                'date_to' => '2020-01-01',
                'is_current' => false,
            ])
            ->assertNotFound();
    }
}
