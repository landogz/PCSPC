<?php

namespace Tests\Feature\Employees;

use App\Models\AuthActivityLog;
use App\Models\Employee;
use App\Models\EmployeeDependent;
use App\Models\User;
use Database\Seeders\AuthSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeDependentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthSeeder::class);
    }

    public function test_admin_can_manage_employee_dependents(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();

        $create = $this->actingAs($admin)
            ->postJson("/api/v1/employees/{$employee->uuid}/dependents", [
                'first_name' => 'Ana',
                'last_name' => 'Demo',
                'relationship' => 'spouse',
                'birth_date' => '1992-05-01',
                'is_beneficiary' => true,
                'is_emergency_contact' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.dependent.relationship', 'spouse')
            ->assertJsonPath('data.dependent.is_beneficiary', true);

        $dependentId = $create->json('data.dependent.id');
        $this->assertNotEmpty($dependentId);
        $this->assertTrue(AuthActivityLog::query()->where('event', 'employee.dependent.created')->exists());

        $this->actingAs($admin)
            ->getJson("/api/v1/employees/{$employee->uuid}/dependents")
            ->assertOk()
            ->assertJsonPath('data.items.0.id', $dependentId)
            ->assertJsonStructure(['data' => ['items', 'relationships']]);

        $this->actingAs($admin)
            ->putJson("/api/v1/employees/{$employee->uuid}/dependents/{$dependentId}", [
                'first_name' => 'Ana Marie',
                'last_name' => 'Demo',
                'relationship' => 'spouse',
                'is_beneficiary' => false,
                'is_emergency_contact' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.dependent.first_name', 'Ana Marie')
            ->assertJsonPath('data.dependent.is_beneficiary', false);

        $this->assertTrue(AuthActivityLog::query()->where('event', 'employee.dependent.updated')->exists());

        $this->actingAs($admin)
            ->deleteJson("/api/v1/employees/{$employee->uuid}/dependents/{$dependentId}")
            ->assertOk();

        $this->assertFalse(EmployeeDependent::query()->where('uuid', $dependentId)->exists());
        $this->assertTrue(AuthActivityLog::query()->where('event', 'employee.dependent.deleted')->exists());
    }

    public function test_dependent_validation_requires_names_and_relationship(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();

        $this->actingAs($admin)
            ->postJson("/api/v1/employees/{$employee->uuid}/dependents", [])
            ->assertStatus(422)
            ->assertJsonPath('errors.first_name.0', 'Please enter a first name.')
            ->assertJsonPath('errors.last_name.0', 'Please enter a last name.')
            ->assertJsonPath('errors.relationship.0', 'Please choose a relationship.');
    }

    public function test_employee_cannot_manage_dependents(): void
    {
        $employeeUser = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();

        $this->actingAs($employeeUser)
            ->getJson("/api/v1/employees/{$employee->uuid}/dependents")
            ->assertStatus(403);

        $this->actingAs($employeeUser)
            ->postJson("/api/v1/employees/{$employee->uuid}/dependents", [
                'first_name' => 'Nope',
                'last_name' => 'Allowed',
                'relationship' => 'child',
            ])
            ->assertStatus(403);
    }

    public function test_dependent_belongs_to_employee_scope(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employeeA = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();
        $employeeB = Employee::query()->where('employee_number', 'EMP-0001')->firstOrFail();

        $dependent = EmployeeDependent::query()->create([
            'employee_id' => $employeeA->id,
            'first_name' => 'Kid',
            'last_name' => 'A',
            'relationship' => 'child',
        ]);

        $this->actingAs($admin)
            ->putJson("/api/v1/employees/{$employeeB->uuid}/dependents/{$dependent->uuid}", [
                'first_name' => 'Kid',
                'last_name' => 'A',
                'relationship' => 'child',
            ])
            ->assertNotFound();
    }
}
